<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Console\Commands\Interfaces\FetchEnabledAiProvidersInterface;
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
        $aiProviders = $this->userConfiguration->getValue('ai_providers');
        if ($aiProviders instanceof Error) {
            return Error::parse('Unable to fetch AI providers.');
        }

        if (! is_array($aiProviders)) {
            return Error::parse('AI providers should be an array.');
        }

        $enabledAiProviders = [];
        foreach ($aiProviders as $aiProvider => $aiProviderConfigurations) {
            $aiProviderAsEnum = AiProvider::parse($aiProvider);
            if ($aiProviderAsEnum instanceof Error) {
                return $aiProviderAsEnum;
            }

            if (! is_array($aiProviderConfigurations)) {
                return Error::parse(
                    "{$aiProviderAsEnum->formattedValue()} AI provider configurations should be an array."
                );
            }

            if (! $aiProviderConfigurations['enabled']) {
                continue;
            }

            $enabledAiProviders[] = $aiProviderAsEnum;
        }

        if ($enabledAiProviders === []) {
            return Error::parse('No enabled AI providers found.');
        }

        return $enabledAiProviders;
    }
}
