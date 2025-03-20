<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Console\GenerateConventionalCommitMessage\GetDefaultLanguageInterface;
use YSOCode\Commit\Domain\Enums\Language;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class GetDefaultLanguageFromUserConfiguration implements GetDefaultLanguageInterface
{
    public function __construct(private UserConfiguration $userConfiguration) {}

    public function execute(): Language|Error
    {
        $language = $this->userConfiguration->getValue('default_lang');

        if (! $language || ! is_string($language)) {
            return Error::parse('Unable to get default language.');
        }

        return Language::parse($language);
    }
}
