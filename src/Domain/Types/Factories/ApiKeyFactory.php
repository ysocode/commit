<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Types\Factories;

use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\CohereApiKey;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Domain\Types\Interfaces\ApiKeyInterface;
use YSOCode\Commit\Domain\Types\SourcegraphApiKey;

class ApiKeyFactory
{
    public static function create(AiProvider $aiProvider, string $apiKey): ApiKeyInterface|Error
    {
        return match ($aiProvider) {
            AiProvider::COHERE => CohereApiKey::parse($apiKey),
            AiProvider::SOURCEGRAPH => SourcegraphApiKey::parse($apiKey),
            default => Error::parse(
                sprintf('Could not create API key for the "%s" AI provider.', $aiProvider->getFormattedValue())
            ),
        };
    }
}
