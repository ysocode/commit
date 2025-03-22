<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Commands\Factories;

use Exception;
use YSOCode\Commit\Application\Actions\GenerateCommitMessageWithSourcegraph;
use YSOCode\Commit\Application\Console\Commands\Interfaces\GenerateCommitMessageInterface;
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
        $checkAiProviderIsEnabled = $this->checkAiProviderIsEnabled($aiProvider);
        if ($checkAiProviderIsEnabled instanceof Error) {
            return $checkAiProviderIsEnabled;
        }

        $apiKey = $this->getApiKey($aiProvider);
        if ($apiKey instanceof Error) {
            return $apiKey;
        }

        return match ($aiProvider) {
            AiProvider::SOURCEGRAPH => new GenerateCommitMessageWithSourcegraph($apiKey),
            default => Error::parse('AI provider commit message generator not found.'),
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

    private function checkAiProviderIsEnabled(AiProvider $aiProvider): bool|Error
    {
        $isEnabled = $this->userConfiguration->getValue("ai_providers.{$aiProvider->value}.enabled");
        if ($isEnabled instanceof Error) {
            return Error::parse('Unable to check if AI provider is enabled.');
        }

        if (! is_bool($isEnabled)) {
            return Error::parse('Invalid AI provider enabled setting. Set a valid boolean value.');
        }

        return $isEnabled;
    }
}
