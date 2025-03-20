<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\GenerateConventionalCommitMessage;

use Exception;
use YSOCode\Commit\Application\Actions\GenerateCommitMessageWithSourcegraph;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class GenerateCommitMessageFactory
{
    public function __construct(private UserConfiguration $userConfiguration) {}

    /**
     * @throws Exception
     */
    public function create(AiProvider $aiProvider): GenerateCommitMessageInterface|Error
    {
        $apiKey = $this->getApiKey($aiProvider);
        if ($apiKey instanceof Error) {
            return $apiKey;
        }

        return match ($aiProvider) {
            AiProvider::SOURCEGRAPH => new GenerateCommitMessageWithSourcegraph($apiKey),
            default => Error::parse('Invalid AI provider.'),
        };
    }

    private function getApiKey(AiProvider $aiProvider): string|Error
    {
        $apiKey = $this->userConfiguration->getValue("ai_providers.{$aiProvider->value}.api_key");
        if ($apiKey instanceof Error) {
            return Error::parse('Unable to retrieve the API key.');
        }

        if (! $apiKey || ! is_string($apiKey)) {
            return Error::parse('Invalid API key. Set a valid API key first.');
        }

        return $apiKey;
    }
}
