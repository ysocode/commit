<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use Symfony\Component\Process\Process;
use YSOCode\Commit\Application\Console\Commands\Abstracts\CommitStagedChangesAbstract;
use YSOCode\Commit\Domain\Types\Error;

class CommitGitStagedChanges extends CommitStagedChangesAbstract
{
    protected function commitStagedChanges(string $commitMessage): true|Error
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
