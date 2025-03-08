<?php

namespace YSOCode\Commit\Actions;

use Closure;
use YSOCode\Commit\Actions\Interfaces\ActionInterface;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Support\Http;

readonly class GenerateCommitWithCohereAI implements ActionInterface
{
    public function __construct(
        private string $systemPrompt,
        private AI $ai,
        private string $gitDiff,
        private Closure $onProgress
    ) {}

    public function execute(): string|Error
    {
        $apiKey = $_ENV[apiKeyEnvVar($this->ai)];
        if (! $apiKey || ! is_string($apiKey)) {
            return Error::parse("No {$this->ai->formattedValue()} API key found");
        }

        $response = Http::create($apiKey)
            ->post(
                'https://api.cohere.com/v2/chat',
                [
                    'model' => 'command-r-plus-08-2024',
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
                ],
                $this->onProgress
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

        return extractCommitMessage($commitMessage);
    }
}
