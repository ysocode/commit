<?php

namespace YSOCode\Commit\Actions;

use Illuminate\Http\Client\Factory;
use Symfony\Component\Process\Process;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Enums\Lang;
use YSOCode\Commit\Domain\Types\Error;

readonly class GetCommitFromGitDiff implements ActionInterface
{
    private string $systemPrompt;

    public function __construct(
        private AI $ai,
        private Lang $lang,
        private string $gitDiff
    ) {
        $this->systemPrompt = <<<PROMPT
        Write a commit message for this diff following Conventional Commits specification.
        ALWAYS wrap the entire commit message between ``` delimiters.
        Do NOT use scopes. 
        EACH line must not exceed 72 characters.
        Write the commit message in {$this->lang->formattedValue()} language without any accents.
        If there are multiple modifications, write the body using the list format.
        DO NOT add a period at the end of each list item, as in the following example:
        - Add a new feature
        - Fix a bug
        PROMPT;
    }

    public function execute(): string|Error
    {
        return match ($this->ai) {
            default => Error::parse('Unsupported AI provider'),
            AI::COHERE => $this->executeCohere(),
            // AI::OPENAI => $this->executeOpenAI(),
            // AI::DEEPSEEK => $this->executeDeepSeek(),
            AI::SOURCEGRAPH => $this->executeSourcegraph(),
        };
    }

    private function executeCohere(): string|Error
    {
        $apiKey = $_ENV[apiKeyEnvVar($this->ai)];
        if (! $apiKey || ! is_string($apiKey)) {
            return Error::parse("No {$this->ai->formattedValue()} API key found");
        }

        $response = $this->requestApi(
            'https://api.cohere.com/v2/chat',
            'command-r-plus-08-2024',
            $apiKey
        );

        if ($response instanceof Error) {
            return $response;
        }

        $message = $response['message'] ?? null;
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

        return $this->extractCommitMessage($commitMessage);
    }

    // private function executeOpenAI(): string|Error {}

    // private function executeDeepSeek(): string|Error {}

    private function executeSourcegraph(): string|Error
    {
        /** @var string $sourcegraphEndpoint */
        $sourcegraphEndpoint = $_ENV['SOURCEGRAPH_API_ENDPOINT'];

        $apiKey = $_ENV[apiKeyEnvVar($this->ai)];
        if (! $apiKey || ! is_string($apiKey)) {
            return Error::parse("No {$this->ai->formattedValue()} API key found");
        }

        $command = ['cody', 'chat', '--stdin', '-m', $this->systemPrompt];

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

    /**
     * @return array<string, mixed>|Error
     */
    private function requestApi(string $url, string $model, string $apiKey): array|Error
    {
        $http = new Factory;
        $response = $http->accept('application/json')
            ->withToken($apiKey)
            ->post($url, [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt,
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
        if (! $responseDecoded || ! is_array($responseDecoded) || ! hasOnlyStringKeys($responseDecoded)) {
            return Error::parse('Invalid JSON response from API or unexpected response format');
        }

        /** @var array<string, mixed> $responseDecoded */
        return $responseDecoded;
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
