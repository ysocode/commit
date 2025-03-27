<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Services\Factories;

use YSOCode\Commit\Application\Services\Interfaces\AiProviderInterface;
use YSOCode\Commit\Application\Services\Sourcegraph;
use YSOCode\Commit\Application\Services\Types\SourcegraphApiKey;
use YSOCode\Commit\Domain\Types\Error;

class AiProviderFactory
{
    public static function create(string $aiProvider, string $apiKey): AiProviderInterface|Error
    {
        if ($aiProvider === 'sourcegraph') {
            if (SourcegraphApiKey::isValid($apiKey)) {
                return Error::parse(sprintf('Invalid "%s" API key.', $aiProvider));
            }

            return new Sourcegraph(
                SourcegraphApiKey::parse($apiKey)
            );
        }

        return Error::parse(sprintf('Invalid "%s" AI provider.', $aiProvider));
    }
}
