<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Console\Commands\Interfaces\CheckLanguageIsEnabledInterface;
use YSOCode\Commit\Application\Console\Commands\Interfaces\GetDefaultLanguageInterface;
use YSOCode\Commit\Domain\Enums\Language;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class GetDefaultLanguageFromUserConfiguration implements GetDefaultLanguageInterface
{
    public function __construct(
        private UserConfiguration $userConfiguration,
        private CheckLanguageIsEnabledInterface $checkLanguageIsEnabled
    ) {}

    public function execute(): Language|Error
    {
        $defaultLanguage = $this->userConfiguration->getValue('default_lang');
        if (! $defaultLanguage || ! is_string($defaultLanguage)) {
            return Error::parse('Unable to get default language.');
        }

        $defaultLanguageAsEnum = Language::parse($defaultLanguage);
        if ($defaultLanguageAsEnum instanceof Error) {
            return $defaultLanguageAsEnum;
        }

        $defaultLanguageIsEnabled = $this->checkLanguageIsEnabled->execute($defaultLanguageAsEnum);
        if ($defaultLanguageIsEnabled instanceof Error) {
            return $defaultLanguageIsEnabled;
        }

        if (! $defaultLanguageIsEnabled) {
            return Error::parse(
                sprintf(
                    'The "%s" language is not enabled.',
                    $defaultLanguageAsEnum->formattedValue()
                )
            );
        }

        return $defaultLanguageAsEnum;
    }
}
