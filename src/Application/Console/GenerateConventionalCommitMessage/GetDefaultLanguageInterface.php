<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\GenerateConventionalCommitMessage;

use YSOCode\Commit\Domain\Enums\Language;
use YSOCode\Commit\Domain\Types\Error;

interface GetDefaultLanguageInterface
{
    public function execute(): Language|Error;
}
