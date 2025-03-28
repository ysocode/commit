<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Commands\Interfaces;

use YSOCode\Commit\Domain\Interfaces\ObservableInterface;
use YSOCode\Commit\Domain\Types\Error;

interface CommitStagedChangesInterface extends ObservableInterface
{
    public function execute(string $commitMessage): true|Error;
}
