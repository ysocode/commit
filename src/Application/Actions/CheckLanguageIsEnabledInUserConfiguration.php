<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Console\Commands\Interfaces\CheckLanguageIsEnabledInterface;
use YSOCode\Commit\Domain\Enums\Language;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class CheckLanguageIsEnabledInUserConfiguration implements CheckLanguageIsEnabledInterface
{
    public function __construct(private UserConfiguration $userConfiguration) {}

    public function execute(Language $language): bool|Error
    {
        $languageIsEnabled = $this->userConfiguration->getValue("languages.{$language->value}.enabled");
        if (! is_bool($languageIsEnabled)) {
            return Error::parse(
                sprintf(
                    'Unable to check if "%s" language is enabled.',
                    $language->formattedValue()
                )
            );
        }

        return $languageIsEnabled;
    }
}
