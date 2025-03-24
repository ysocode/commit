<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Commands\Abstracts;

use YSOCode\Commit\Application\Console\Commands\Traits\WithObserverToolsTrait;
use YSOCode\Commit\Domain\Enums\Status;
use YSOCode\Commit\Domain\Types\Error;

abstract class CommitStagedChangesAbstract
{
    use WithObserverToolsTrait;

    public function execute(string $commitMessage): true|Error
    {
        $this->notify(Status::STARTED);

        $commitStagedChangesReturn = $this->commitStagedChanges($commitMessage);
        if ($commitStagedChangesReturn instanceof Error) {
            return $commitStagedChangesReturn;
        }

        $this->notify(Status::FINISHED);

        return true;
    }

    abstract protected function commitStagedChanges(string $commitMessage): true|Error;
}
