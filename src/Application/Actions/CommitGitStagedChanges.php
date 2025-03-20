<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use Symfony\Component\Process\Process;
use YSOCode\Commit\Application\Console\GenerateConventionalCommitMessage\CommitStagedChangesInterface;
use YSOCode\Commit\Domain\Types\Error;

class CommitGitStagedChanges implements CommitStagedChangesInterface
{
    public function execute(string $commitMessage): true|Error
    {
        $commitGitStagedChangesProcess = new Process(['git', 'commit', '-m', $commitMessage]);
        $commitGitStagedChangesProcess->run();

        if (! $commitGitStagedChangesProcess->isSuccessful()) {
            return Error::parse($commitGitStagedChangesProcess->getErrorOutput());
        }

        return true;
    }
}
