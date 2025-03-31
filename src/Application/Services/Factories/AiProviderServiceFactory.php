<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Services\Factories;

use YSOCode\Commit\Application\Commands\Interfaces\GetDefaultModelInterface;
use YSOCode\Commit\Application\Services\Cohere;
use YSOCode\Commit\Application\Services\Interfaces\AiProviderServiceInterface;
use YSOCode\Commit\Application\Services\Sourcegraph;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\CohereApiKey;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Domain\Types\Interfaces\ApiKeyInterface;
use YSOCode\Commit\Domain\Types\SourcegraphApiKey;

readonly class AiProviderServiceFactory
{
    public function __construct(private GetDefaultModelInterface $getDefaultModel) {}

    public function create(AiProvider $aiProvider, ApiKeyInterface $apiKey): AiProviderServiceInterface|Error
    {
        return match ($aiProvider) {
            AiProvider::COHERE => self::createCohereService($aiProvider, $apiKey),
            AiProvider::SOURCEGRAPH => self::createSourcegraphService($aiProvider, $apiKey),
            default => Error::parse(
                sprintf('Could not find "%s" AI provider service.', $aiProvider->getFormattedValue())
            )
        };
    }

    private function createCohereService(AiProvider $aiProvider, ApiKeyInterface $apiKey): Cohere|Error
    {
        if (! $apiKey instanceof CohereApiKey) {
            return Error::parse(
                sprintf('Invalid API key for "%s" AI provider service.', $aiProvider->getFormattedValue())
            );
        }

        $defaultModel = $this->getDefaultModel->execute($aiProvider);
        if ($defaultModel instanceof Error) {
            return $defaultModel;
        }

        return new Cohere($apiKey, $defaultModel, 0.2);
    }

    private function createSourcegraphService(AiProvider $aiProvider, ApiKeyInterface $apiKey): Sourcegraph|Error
    {
        if (! $apiKey instanceof SourcegraphApiKey) {
            return Error::parse(
                sprintf('Invalid API key for "%s" AI provider service.', $aiProvider->getFormattedValue())
            );
        }

        return new Sourcegraph($apiKey);
    }
}
