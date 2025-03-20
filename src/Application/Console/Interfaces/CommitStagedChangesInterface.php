<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Interfaces;

use YSOCode\Commit\Domain\Types\Error;

interface CommitStagedChangesInterface
{
    public function execute(string $commitMessage): true|Error;
}
