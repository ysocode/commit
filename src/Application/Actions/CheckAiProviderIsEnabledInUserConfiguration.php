<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Commands\Interfaces\CheckAiProviderIsEnabledInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class CheckAiProviderIsEnabledInUserConfiguration implements CheckAiProviderIsEnabledInterface
{
    public function __construct(private UserConfiguration $userConfiguration) {}

    public function execute(AiProvider $aiProvider): bool|Error
    {
        $aiProviderIsEnabled = $this->userConfiguration->getValue("ai_providers.{$aiProvider->value}.enabled");
        if (! is_bool($aiProviderIsEnabled)) {
            return Error::parse(
                sprintf(
                    'Unable to check if "%s" AI provider is enabled.',
                    $aiProvider->getFormattedValue()
                )
            );
        }

        return $aiProviderIsEnabled;
    }
}
