<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Commands\Interfaces;

use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;

interface CheckAiProviderIsEnabledInterface
{
    public function execute(AiProvider $aiProvider): bool|Error;
}
