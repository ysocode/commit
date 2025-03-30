<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Commands\Interfaces\FetchEnabledAiProvidersInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class FetchEnabledAiProvidersFromUserConfiguration implements FetchEnabledAiProvidersInterface
{
    public function __construct(private UserConfiguration $userConfiguration) {}

    /**
     * @return array<AiProvider>|Error
     */
    public function execute(): array|Error
    {
        $aiProviderNames = $this->userConfiguration->getValue('ai_providers');
        if ($aiProviderNames instanceof Error) {
            return Error::parse('Unable to fetch AI providers.');
        }

        if (! is_array($aiProviderNames)) {
            return Error::parse('AI providers should be an array.');
        }

        $enabledAiProviders = [];
        foreach ($aiProviderNames as $aiProviderName => $aiProviderConfigurations) {
            $aiProvider = AiProvider::parse($aiProviderName);
            if ($aiProvider instanceof Error) {
                return $aiProvider;
            }

            if (! is_array($aiProviderConfigurations)) {
                return Error::parse(
                    "{$aiProvider->getFormattedValue()} AI provider configurations should be an array."
                );
            }

            if (! $aiProviderConfigurations['enabled']) {
                continue;
            }

            $enabledAiProviders[] = $aiProvider;
        }

        if ($enabledAiProviders === []) {
            return Error::parse('No enabled AI providers found.');
        }

        return $enabledAiProviders;
    }
}
