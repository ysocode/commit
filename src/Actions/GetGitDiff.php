<?php

namespace YSOCode\Commit\Actions;

use Symfony\Component\Process\Process;
use YSOCode\Commit\Domain\Error;

class GetGitDiff implements Action
{
    public function execute(): string|Error
    {
        $process = new Process(['git', 'diff', '--staged']);
        $process->run();

        if (! $process->isSuccessful()) {
            return Error::parse('Unable to retrieve the Git diff');
        }

        $gitDiff = $process->getOutput();

        if (! $gitDiff) {
            return Error::parse('No changes found in the Git diff');
        }

        return $gitDiff;
    }
}
