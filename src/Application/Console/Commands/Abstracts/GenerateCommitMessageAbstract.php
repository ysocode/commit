<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Commands\Abstracts;

use YSOCode\Commit\Application\Console\Commands\Traits\WithObserverToolsTrait;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Enums\Status;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

abstract class GenerateCommitMessageAbstract
{
    use WithObserverToolsTrait;

    public function __construct(
        protected readonly AiProvider $aiProvider,
        protected readonly UserConfiguration $userConfiguration,
        protected readonly string $prompt,
        protected readonly string $diff
    ) {}

    public function execute(): string|Error
    {
        $this->notify(Status::STARTED);

        $checkAiProviderIsEnabled = $this->checkAiProviderIsEnabled();
        if ($checkAiProviderIsEnabled instanceof Error) {
            $this->notify(Status::FAILED);

            return $checkAiProviderIsEnabled;
        }

        $apiKey = $this->getApiKey();
        if ($apiKey instanceof Error) {
            $this->notify(Status::FAILED);

            return $apiKey;
        }

        $commitMessage = $this->generateCommitMessage($apiKey);
        if ($commitMessage instanceof Error) {
            $this->notify(Status::FAILED);

            return $commitMessage;
        }

        $this->notify(Status::FINISHED);

        return $commitMessage;
    }

    protected function getApiKey(): string|Error
    {
        $apiKey = $this->userConfiguration->getValue("ai_providers.{$this->aiProvider->value}.api_key");
        if ($apiKey instanceof Error) {
            return Error::parse('Unable to retrieve the API key.');
        }

        if (! $apiKey || ! is_string($apiKey)) {
            return Error::parse('Invalid API key. Set a valid API key first.');
        }

        return $apiKey;
    }

    protected function checkAiProviderIsEnabled(): bool|Error
    {
        $aiProviderIsEnabled = $this->userConfiguration->getValue("ai_providers.{$this->aiProvider->value}.enabled");
        if (! is_bool($aiProviderIsEnabled)) {
            return Error::parse(
                sprintf(
                    'Unable to check if "%s" AI provider is enabled.',
                    $this->aiProvider->value
                )
            );
        }

        return $aiProviderIsEnabled;
    }

    abstract protected function generateCommitMessage(string $apiKey): string|Error;
}
