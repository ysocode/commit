<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use Symfony\Component\Process\Process;
use YSOCode\Commit\Domain\Types\Error;

final readonly class FetchStagedGitChanges implements ActionInterface
{
    public function execute(): string|Error
    {
        $gitDiffProcess = new Process(['git', 'diff', '--staged']);
        $gitDiffProcess->run();

        if (! $gitDiffProcess->isSuccessful()) {
            return Error::parse('Unable to retrieve the Git diff.');
        }

        $gitDiff = $gitDiffProcess->getOutput();

        if ($gitDiff === '' || $gitDiff === '0') {
            return Error::parse('No changes found in the Git diff.');
        }

        return $gitDiff;
    }
}
