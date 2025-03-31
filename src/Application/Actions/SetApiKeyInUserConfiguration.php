<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Commands\Interfaces\SetApiKeyInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\CohereApiKey;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Domain\Types\Interfaces\ApiKeyInterface;
use YSOCode\Commit\Domain\Types\SourcegraphApiKey;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class SetApiKeyInUserConfiguration implements SetApiKeyInterface
{
    public function __construct(private UserConfiguration $userConfiguration) {}

    public function execute(AiProvider $aiProvider, ApiKeyInterface $apiKey): true|Error
    {
        $apiKeyType = match ($aiProvider) {
            AiProvider::COHERE => CohereApiKey::class,
            AiProvider::SOURCEGRAPH => SourcegraphApiKey::class,
            default => Error::parse(
                sprintf(
                    'Could not find an API key type for "%s" AI provider.',
                    $aiProvider->getFormattedValue()
                )
            ),
        };

        if ($apiKeyType instanceof Error) {
            return $apiKeyType;
        }

        if (! $apiKey instanceof $apiKeyType) {
            return Error::parse(
                sprintf('Invalid API key for "%s" AI provider.', $aiProvider->getFormattedValue())
            );
        }

        $apiKeyIsSet = $this->userConfiguration->setValue("ai_providers.{$aiProvider->value}.api_key", (string) $apiKey);
        if ($apiKeyIsSet instanceof Error) {
            return $apiKeyIsSet;
        }

        return true;
    }
}
