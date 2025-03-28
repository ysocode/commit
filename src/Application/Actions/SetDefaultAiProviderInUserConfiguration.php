<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Commands\Interfaces\SetDefaultAiProviderInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class SetDefaultAiProviderInUserConfiguration implements SetDefaultAiProviderInterface
{
    public function __construct(private UserConfiguration $userConfiguration) {}

    public function execute(AiProvider $aiProvider): true|Error
    {
        $aiProviderIsEnabled = $this->checkAiProviderIsEnabled($aiProvider);
        if ($aiProviderIsEnabled instanceof Error) {
            return $aiProviderIsEnabled;
        }

        if (! $aiProviderIsEnabled) {
            return Error::parse(
                sprintf('AI provider "%s" is not enabled.', $aiProvider->value)
            );
        }

        $valueIsSet = $this->userConfiguration->setValue('default_ai_provider', $aiProvider->value);
        if ($valueIsSet instanceof Error) {
            return Error::parse('Unable to set default AI provider.');
        }

        return true;
    }

    protected function checkAiProviderIsEnabled(AiProvider $aiProvider): bool|Error
    {
        $isEnabled = $this->userConfiguration->getValue("ai_providers.{$aiProvider->value}.enabled");
        if ($isEnabled instanceof Error) {
            return Error::parse('Unable to check if AI provider is enabled.');
        }

        if (! is_bool($isEnabled)) {
            return Error::parse('Invalid AI provider enabled setting. Set a valid boolean value.');
        }

        return $isEnabled;
    }
}
