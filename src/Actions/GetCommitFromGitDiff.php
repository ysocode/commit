<?php

namespace YSOCode\Commit\Actions;

use Illuminate\Http\Client\Factory;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;

readonly class GetCommitFromGitDiff implements ActionInterface
{
    public function __construct(
        private AI $ai,
        private string $gitDiff
    ) {}

    public function execute(): string|Error
    {
        $envName = strtoupper($this->ai->value).'_API_KEY';

        $apiKey = $_ENV[$envName];
        if (! $apiKey || ! is_string($apiKey)) {
            return Error::parse("No {$this->ai->formattedValue()} API key found");
        }

        $systemPrompt = <<<'PROMPT'
        You are an AI-powered Git commit message generator. Your task is to analyze Git diffs and produce clean,
        standardized commit messages.
        
        Guidelines for generating commit messages:
        1. Follow the Conventional Commits specification (e.g., 'feat: add user authentication')
        2. NEVER include file names or directory paths in the message
        3. NEVER use scopes in commit messages (e.g., use 'fix: resolve login error' NOT 'fix(auth): resolve login error')
        4. Keep messages concise and under 72 characters per line
        5. Focus on WHAT changed and WHY, not HOW it changed
        6. Use imperative present tense (e.g., "add" not "added" or "adds")
        
        IMPORTANT: Return ONLY the raw commit message with no additional formatting, quotes, backticks, explanations 
        or commentary. The response should contain nothing except the commit message itself.
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
}
