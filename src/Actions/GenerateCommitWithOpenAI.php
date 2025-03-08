<?php

namespace YSOCode\Commit\Actions;

use Closure;
use YSOCode\Commit\Actions\Interfaces\ActionInterface;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Support\Http;

readonly class GenerateCommitWithOpenAI implements ActionInterface
{
    public function __construct(
        private string $developerPrompt,
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
                'https://api.openai.com/v1/chat/completions',
                [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'developer',
                            'content' => $this->developerPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->gitDiff,
                        ],
                    ],
                    'temperature' => 0.2,
                    'stream' => false,
                ],
                $this->onProgress
            );

        if ($response instanceof Error) {
            return $response;
        }
    }
}
