<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Commands\Interfaces\GetApiKeyInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Domain\Types\Factories\ApiKeyFactory;
use YSOCode\Commit\Domain\Types\Interfaces\ApiKeyInterface;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class GetApiKeyFromUserConfiguration implements GetApiKeyInterface
{
    public function __construct(private UserConfiguration $userConfiguration) {}

    public function execute(AiProvider $aiProvider): ApiKeyInterface|Error
    {
        $apiKey = $this->userConfiguration->getValue("ai_providers.{$aiProvider->value}.api_key");
        if ($apiKey instanceof Error) {
            return Error::parse(sprintf('Could not retrieve API key for "%s" AI provider.', $aiProvider->formattedValue()));
        }

        if (! $apiKey || ! is_string($apiKey)) {
            return Error::parse(
                sprintf('Invalid API key for "%s" AI provider.', $aiProvider->formattedValue())
            );
        }

        return ApiKeyFactory::create($aiProvider, $apiKey);
    }
}
