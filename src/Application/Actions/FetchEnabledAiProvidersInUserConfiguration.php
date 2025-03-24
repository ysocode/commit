<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Console\Commands\Interfaces\FetchEnabledAiProvidersInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class FetchEnabledAiProvidersInUserConfiguration implements FetchEnabledAiProvidersInterface
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

        $convertedAiProviders = [];
        foreach ($aiProviders as $aiProvider => $aiProviderConfigurations) {
            $convertedAiProvider = AiProvider::parse($aiProvider);
            if ($convertedAiProvider instanceof Error) {
                return $convertedAiProvider;
            }

            if (! is_array($aiProviderConfigurations)) {
                return Error::parse(
                    "{$convertedAiProvider->formattedValue()} AI provider configurations should be an array."
                );
            }

            if (! $aiProviderConfigurations['enabled']) {
                continue;
            }

            $convertedAiProviders[] = $convertedAiProvider;
        }

        if ($convertedAiProviders === []) {
            return Error::parse('No enabled AI providers found.');
        }

        return $convertedAiProviders;
    }
}
