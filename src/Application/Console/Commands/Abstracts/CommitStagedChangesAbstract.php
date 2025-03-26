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

        $commitIsMade = $this->commitStagedChanges($commitMessage);
        if ($commitIsMade instanceof Error) {
            return $commitIsMade;
        }

        $this->notify(Status::FINISHED);

        return true;
    }

    abstract protected function commitStagedChanges(string $commitMessage): true|Error;
}
