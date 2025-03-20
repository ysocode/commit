<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Interfaces;

use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;

interface GetDefaultAiProviderInterface
{
    public function execute(): AiProvider|Error;
}
