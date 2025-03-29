<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Types\Factories;

use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Domain\Types\Interfaces\ApiKeyInterface;
use YSOCode\Commit\Domain\Types\SourcegraphApiKey;

class ApiKeyFactory
{
    public static function create(AiProvider $aiProvider, string $apiKey): ApiKeyInterface|Error
    {
        return match ($aiProvider) {
            AiProvider::SOURCEGRAPH => self::createSourcegraphApiKey($apiKey),
            default => Error::parse(
                sprintf('Could not create API key for the "%s" AI provider.', $aiProvider->getFormattedValue())
            ),
        };
    }

    private static function createSourcegraphApiKey(string $apiKey): SourcegraphApiKey|Error
    {
        if (SourcegraphApiKey::isValid($apiKey)) {
            return new SourcegraphApiKey($apiKey);
        }

        return Error::parse(
            sprintf('Invalid API key for the "%s" AI provider.', AiProvider::SOURCEGRAPH->getFormattedValue())
        );
    }
}
