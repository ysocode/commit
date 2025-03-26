<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use Symfony\Component\Process\Process;
use YSOCode\Commit\Application\Console\Commands\Abstracts\GenerateCommitMessageAbstract;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

class GenerateCommitMessageWithSourcegraph extends GenerateCommitMessageAbstract
{
    public function __construct(
        UserConfiguration $userConfiguration,
        string $prompt,
        string $diff
    ) {
        parent::__construct(
            AiProvider::SOURCEGRAPH,
            $userConfiguration,
            $prompt,
            $diff
        );
    }

    protected function generateCommitMessage(string $apiKey): string|Error
    {
        $checkCodyInstallationReturn = $this->checkCodyInstallation();
        if ($checkCodyInstallationReturn instanceof Error) {
            return $checkCodyInstallationReturn;
        }

        $codyProcess = new Process(
            ['cody', 'chat', '--stdin', '-m', $this->prompt],
            null,
            [
                'SRC_ENDPOINT' => 'https://sourcegraph.com',
                'SRC_ACCESS_TOKEN' => $apiKey,
            ],
            $this->diff
        );

        $codyProcess->start();

        while ($codyProcess->isRunning()) {
            $this->notifyRunningStatus();
        }

        if (! $codyProcess->isSuccessful()) {
            return Error::parse($codyProcess->getErrorOutput());
        }

        $commitMessage = $codyProcess->getOutput();
        if ($commitMessage === '' || $commitMessage === '0') {
            return Error::parse('Unable to generate commit message.');
        }

        return $commitMessage;
    }

    private function checkCodyInstallation(): true|Error
    {
        $process = new Process(['which', 'cody']);
        $process->run();

        if (! $process->isSuccessful()) {
            return Error::parse('Cody is not installed.');
        }

        return true;
    }
}
