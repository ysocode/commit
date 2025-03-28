<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use Symfony\Component\Process\Process;
use YSOCode\Commit\Application\Console\Commands\Interfaces\CommitStagedChangesInterface;
use YSOCode\Commit\Domain\Traits\WithObserverToolsTrait;
use YSOCode\Commit\Domain\Types\Error;

class CommitGitStagedChanges implements CommitStagedChangesInterface
{
    use WithObserverToolsTrait;

    public function execute(string $commitMessage): true|Error
    {
        $commitGitStagedChangesProcess = new Process(['git', 'commit', '-m', $commitMessage]);
        $commitGitStagedChangesProcess->start();

        while ($commitGitStagedChangesProcess->isRunning()) {
            $this->notifyRunningStatus();
        }

        if (! $commitGitStagedChangesProcess->isSuccessful()) {
            return Error::parse($commitGitStagedChangesProcess->getErrorOutput());
        }

        return true;
    }
}
