<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Console\GenerateConventionalCommitMessage\GetDefaultAiProviderInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\LocalConfiguration;

final readonly class GetDefaultAiProviderFromLocalConfiguration implements GetDefaultAiProviderInterface
{
    public function __construct(private LocalConfiguration $localConfiguration) {}

    public function execute(): AiProvider|Error
    {
        $aiProvider = $this->localConfiguration->getValue('default_ai_provider');

        if (! $aiProvider || ! is_string($aiProvider)) {
            return Error::parse('Unable to get default ai_provider.');
        }

        return AiProvider::parse($aiProvider);
    }
}
