<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Commands\Interfaces;

use YSOCode\Commit\Domain\Enums\Language;
use YSOCode\Commit\Domain\Types\Error;

interface CheckLanguageIsEnabledInterface
{
    public function execute(Language $language): bool|Error;
}
