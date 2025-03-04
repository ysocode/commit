<?php

namespace YSOCode\Commit\Actions;

use Illuminate\Http\Client\Factory;
use Symfony\Component\Process\Process;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Enums\Lang;
use YSOCode\Commit\Domain\Types\Error;

readonly class GetCommitFromGitDiff implements ActionInterface
{
    public function __construct(
        private AI $ai,
        private Lang $lang,
        private string $gitDiff
    ) {}

    public function execute(): string|Error
    {
        $aiProviderMethod = "execute{$this->ai->formattedValue()}";
        if (method_exists($this, $aiProviderMethod)) {
            $aiProviderMethodReturn = $this->{$aiProviderMethod}();
            if (! is_string($aiProviderMethodReturn) && ! $aiProviderMethodReturn instanceof Error) {
                return Error::parse('AI method returned an invalid value');
            }

            return $aiProviderMethodReturn;
        }

        $apiKey = $_ENV[$this->ai->apiKeyEnvVar()];
        if (! $apiKey || ! is_string($apiKey)) {
            return Error::parse("No {$this->ai->formattedValue()} API key found");
        }

        $systemPrompt = <<<PROMPT
        You are an AI-powered Git commit message generator. Follow the Conventional Commits specification.
        
        Guidelines:
        - The commit message must have a **short title** (max 72 characters).
        - The title should describe **what** was changed (e.g., 'feat: add user authentication').
        - **Do not include file names or paths**.
        - The body should explain **what** was changed and **why**, using a **concise, detailed description** (up to 200 characters).
        - Use **imperative present tense** (e.g., "add", not "added" or "adds").
        - Do not use scopes in commit messages.
        
        Types of commits: feat, fix, docs, style, refactor, perf, test, chore.
        
        **Language**: {$this->lang->formattedValue()}
        
        IMPORTANT: 
        1. The output should be only the **commit message** (no extra formatting, quotes, or commentary).
        2. The commit message should be divided into two parts:
            - **Title**: The first line should contain a short description of the change (under 72 characters).
            - **Body**: The second part should explain **what** and **why** the change was made, keeping it concise and clear.
        
        Return the commit message in this format:
        [TITLE]
        
        [BODY]
        
        The title and body should be separated by a blank line.
        PROMPT;

        ['url' => $url, 'model' => $model] = $this->ai->apiConfig();

        $http = new Factory;
        $response = $http->accept('application/json')
            ->withToken($apiKey)
            ->post($url, [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->gitDiff,
                    ],
                ],
                'temperature' => 0.7,
                'stream' => false,
            ]);

        if ($response->getStatusCode() !== 200) {
            return Error::parse(
                "Request to {$this->ai->formattedValue()} API failed with status code {$response->getStatusCode()}"
            );
        }

        $responseDecoded = json_decode($response->getBody(), true);
        if (! $responseDecoded || ! is_array($responseDecoded)) {
            return Error::parse('Invalid JSON response from API or unexpected response format');
        }

        $message = $responseDecoded['message'] ?? null;
        if (! $message || ! is_array($message)) {
            return Error::parse('Missing or invalid "message" field in API response');
        }

        $content = $message['content'] ?? null;
        if (! $content || ! is_array($content)) {
            return Error::parse('Missing or invalid "content" field in message structure');
        }

        $contentFirstItem = $content[0] ?? null;
        if (! $contentFirstItem || ! is_array($contentFirstItem)) {
            return Error::parse('Empty content array or invalid first content item');
        }

        $commitMessage = $contentFirstItem['text'] ?? null;
        if (! $commitMessage || ! is_string($commitMessage)) {
            return Error::parse('Missing or non-string commit message text in response');
        }

        return $commitMessage;
    }

    private function executeSourcegraph(): string|Error
    {
        /** @var string $sourcegraphEndpoint */
        $sourcegraphEndpoint = $_ENV['SOURCEGRAPH_API_ENDPOINT'];

        $apiKey = $_ENV[$this->ai->apiKeyEnvVar()];
        if (! $apiKey || ! is_string($apiKey)) {
            return Error::parse("No {$this->ai->formattedValue()} API key found");
        }

        $systemPrompt = <<<'PROMPT'
        Write a commit message for this diff following Conventional Commits specification:
        
        IMPORTANT GUIDELINES:
        - ALWAYS wrap the entire commit message between ``` delimiters
        - Do NOT use scopes 
        - EACH line must not exceed 72 characters
        - Use imperative present tense
        PROMPT;

        $command = ['cody', 'chat', '--stdin', '-m', $systemPrompt];

        $codyProcess = new Process(
            $command,
            null,
            [
                'SRC_ENDPOINT' => $sourcegraphEndpoint,
                'SRC_ACCESS_TOKEN' => $apiKey,
            ],
            $this->gitDiff
        );
        $codyProcess->run();

        if (! $codyProcess->isSuccessful()) {
            return Error::parse('Unable to retrieve the commit from Git diff');
        }

        return $this->extractCommitMessage($codyProcess->getOutput());
    }

    private function extractCommitMessage(string $commitMessage): string|Error
    {
        $pattern = '/```(.*?)```/s';

        if (preg_match($pattern, $commitMessage, $matches)) {
            $commitMessage = trim($matches[1]);

            if (empty($commitMessage)) {
                return Error::parse('Extracted commit message is empty');
            }

            return $commitMessage;
        }

        return Error::parse('Unable to extract commit message');
    }
}
