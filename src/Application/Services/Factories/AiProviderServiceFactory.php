<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Services\Factories;

use YSOCode\Commit\Application\Services\Interfaces\AiProviderServiceInterface;
use YSOCode\Commit\Application\Services\Sourcegraph;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Domain\Types\Interfaces\ApiKeyInterface;
use YSOCode\Commit\Domain\Types\SourcegraphApiKey;

class AiProviderServiceFactory
{
    public static function create(AiProvider $aiProvider, ApiKeyInterface $apiKey): AiProviderServiceInterface|Error
    {
        $createAiProviderService = match ($aiProvider) {
            AiProvider::SOURCEGRAPH => function (ApiKeyInterface $apiKey) use ($aiProvider): AiProviderServiceInterface|Error {
                if (! $apiKey instanceof SourcegraphApiKey) {
                    return Error::parse(
                        sprintf('Invalid API key for "%s" AI provider service.', $aiProvider->getFormattedValue())
                    );
                }

                return new Sourcegraph($apiKey);
            },
            default => Error::parse(
                sprintf('Could not find "%s" AI provider service.', $aiProvider->getFormattedValue())
            )
        };

        if ($createAiProviderService instanceof Error) {
            return $createAiProviderService;
        }

        return $createAiProviderService($apiKey);
    }
}
