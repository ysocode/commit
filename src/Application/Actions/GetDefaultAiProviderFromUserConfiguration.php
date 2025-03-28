<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Commands\Interfaces\CheckAiProviderIsEnabledInterface;
use YSOCode\Commit\Application\Commands\Interfaces\GetDefaultAiProviderInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class GetDefaultAiProviderFromUserConfiguration implements GetDefaultAiProviderInterface
{
    public function __construct(
        private UserConfiguration $userConfiguration,
        private CheckAiProviderIsEnabledInterface $checkAiProviderIsEnabled
    ) {}

    public function execute(): AiProvider|Error
    {
        $defaultAiProvider = $this->userConfiguration->getValue('default_ai_provider');
        if (! $defaultAiProvider || ! is_string($defaultAiProvider)) {
            return Error::parse('Unable to get default AI provider.');
        }

        $defaultAiProviderAsEnum = AiProvider::parse($defaultAiProvider);
        if ($defaultAiProviderAsEnum instanceof Error) {
            return $defaultAiProviderAsEnum;
        }

        $defaultAiProviderIsEnabled = $this->checkAiProviderIsEnabled->execute($defaultAiProviderAsEnum);
        if ($defaultAiProviderIsEnabled instanceof Error) {
            return $defaultAiProviderIsEnabled;
        }

        if (! $defaultAiProviderIsEnabled) {
            return Error::parse(
                sprintf(
                    'The "%s" AI provider is not enabled.',
                    $defaultAiProviderAsEnum->formattedValue()
                )
            );
        }

        return $defaultAiProviderAsEnum;
    }
}
