<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Commands\Interfaces;

use YSOCode\Commit\Domain\Types\Error;

interface FetchStagedChangesInterface
{
    public function execute(): string|Error;
}
