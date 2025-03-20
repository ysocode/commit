<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Console\Commands\Interfaces\GetDefaultAiProviderInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class GetDefaultAiProviderFromUserConfiguration implements GetDefaultAiProviderInterface
{
    public function __construct(private UserConfiguration $userConfiguration) {}

    public function execute(): AiProvider|Error
    {
        $aiProvider = $this->userConfiguration->getValue('default_ai_provider');

        if (! $aiProvider || ! is_string($aiProvider)) {
            return Error::parse('Unable to get default AI provider.');
        }

        return AiProvider::parse($aiProvider);
    }
}
