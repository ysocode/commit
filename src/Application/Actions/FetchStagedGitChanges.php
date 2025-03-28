<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use Symfony\Component\Process\Process;
use YSOCode\Commit\Application\Commands\Interfaces\FetchStagedChangesInterface;
use YSOCode\Commit\Domain\Types\Error;

readonly class FetchStagedGitChanges implements FetchStagedChangesInterface
{
    public function execute(): string|Error
    {
        $gitDiffProcess = new Process(['git', 'diff', '--staged']);
        $gitDiffProcess->run();

        if (! $gitDiffProcess->isSuccessful()) {
            return Error::parse('Unable to retrieve the Git staged changes.');
        }

        $gitDiff = $gitDiffProcess->getOutput();

        if ($gitDiff === '' || $gitDiff === '0') {
            return Error::parse('No Git staged changes found.');
        }

        return $gitDiff;
    }
}
