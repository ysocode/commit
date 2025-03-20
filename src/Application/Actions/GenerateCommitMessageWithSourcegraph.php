<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use Symfony\Component\Process\Process;
use YSOCode\Commit\Application\Console\Interfaces\GenerateCommitMessageInterface;
use YSOCode\Commit\Domain\Enums\Status;
use YSOCode\Commit\Domain\Types\Error;

class GenerateCommitMessageWithSourcegraph implements GenerateCommitMessageInterface
{
    use WithObserverToolsTrait;

    public function __construct(private readonly string $apiKey) {}

    public function execute(string $prompt, string $diff): string|Error
    {
        $checkCodyInstallationReturn = $this->checkCodyInstallation();
        if ($checkCodyInstallationReturn instanceof Error) {
            return $checkCodyInstallationReturn;
        }

        $codyProcess = new Process(
            ['cody', 'chat', '--stdin', '-m', $prompt],
            null,
            [
                'SRC_ENDPOINT' => 'https://sourcegraph.com',
                'SRC_ACCESS_TOKEN' => $this->apiKey,
            ],
            $diff
        );

        $codyProcess->start();

        while ($codyProcess->isRunning()) {
            $this->notify(Status::RUNNING);
        }

        $codyProcess->wait();

        if (! $codyProcess->isSuccessful()) {
            return Error::parse('Unable to retrieve the commit from diff.');
        }

        $commitMessage = $codyProcess->getOutput();
        if ($commitMessage === '' || $commitMessage === '0') {
            return Error::parse('Unable to retrieve the commit from diff.');
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
