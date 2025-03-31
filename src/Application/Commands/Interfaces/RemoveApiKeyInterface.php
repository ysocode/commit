<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Commands\Interfaces;

use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;

interface RemoveApiKeyInterface
{
    public function execute(AiProvider $provider): true|Error;
}
