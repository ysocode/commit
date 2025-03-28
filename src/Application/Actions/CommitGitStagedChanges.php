<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use Symfony\Component\Process\Process;
use YSOCode\Commit\Application\Commands\Interfaces\CommitStagedChangesInterface;
use YSOCode\Commit\Domain\Enums\Status;
use YSOCode\Commit\Domain\Traits\WithObserverToolsTrait;
use YSOCode\Commit\Domain\Types\Error;

class CommitGitStagedChanges implements CommitStagedChangesInterface
{
    use WithObserverToolsTrait;

    public function execute(string $commitMessage): true|Error
    {
        $this->notify(Status::STARTED);

        $commitGitStagedChangesProcess = new Process(['git', 'commit', '-m', $commitMessage]);
        $commitGitStagedChangesProcess->start();

        while ($commitGitStagedChangesProcess->isRunning()) {
            $this->notifyRunningStatus();
        }

        if (! $commitGitStagedChangesProcess->isSuccessful()) {
            $this->notify(Status::FAILED);

            return Error::parse($commitGitStagedChangesProcess->getErrorOutput());
        }

        $this->notify(Status::FINISHED);

        return true;
    }
}
