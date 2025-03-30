<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Commands\Interfaces\CheckLanguageIsEnabledInterface;
use YSOCode\Commit\Application\Commands\Interfaces\GetDefaultLanguageInterface;
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
        $defaultLanguageName = $this->userConfiguration->getValue('default_lang');
        if (! $defaultLanguageName || ! is_string($defaultLanguageName)) {
            return Error::parse('Unable to get default language.');
        }

        $defaultLanguage = Language::parse($defaultLanguageName);
        if ($defaultLanguage instanceof Error) {
            return $defaultLanguage;
        }

        $defaultLanguageIsEnabled = $this->checkLanguageIsEnabled->execute($defaultLanguage);
        if ($defaultLanguageIsEnabled instanceof Error) {
            return $defaultLanguageIsEnabled;
        }

        if (! $defaultLanguageIsEnabled) {
            return Error::parse(
                sprintf(
                    'The "%s" language is not enabled.',
                    $defaultLanguage->getFormattedValue()
                )
            );
        }

        return $defaultLanguage;
    }
}
