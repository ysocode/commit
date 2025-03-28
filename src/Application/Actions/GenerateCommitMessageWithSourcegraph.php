<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use Symfony\Component\Process\Process;
use YSOCode\Commit\Application\Console\Commands\Interfaces\GenerateCommitMessageInterface;
use YSOCode\Commit\Application\Console\Commands\Interfaces\GetApiKeyInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Enums\Status;
use YSOCode\Commit\Domain\Traits\WithObserverToolsTrait;
use YSOCode\Commit\Domain\Types\Error;

class GenerateCommitMessageWithSourcegraph implements GenerateCommitMessageInterface
{
    use WithObserverToolsTrait;

    public function __construct(
        private readonly GetApiKeyInterface $getApiKey,
    ) {}

    public function execute(AiProvider $aiProvider, string $prompt, string $diff): string|Error
    {
        $this->notify(Status::STARTED);

        $codyIsInstalled = $this->checkCodyIsInstalled();
        if (! $codyIsInstalled) {
            $this->notify(Status::FAILED);

            return Error::parse('Cody is not installed.');
        }

        $apiKey = $this->getApiKey->execute($aiProvider);
        if ($apiKey instanceof Error) {
            $this->notify(Status::FAILED);

            return $apiKey;
        }

        $codyGenerateCommitMessageProcess = new Process(
            ['cody', 'chat', '--stdin', '-m', $prompt],
            null,
            [
                'SRC_ENDPOINT' => 'https://sourcegraph.com',
                'SRC_ACCESS_TOKEN' => (string) $apiKey,
            ],
            $this->$diff
        );

        $codyGenerateCommitMessageProcess->start();

        while ($codyGenerateCommitMessageProcess->isRunning()) {
            $this->notifyRunningStatus();
        }

        if (! $codyGenerateCommitMessageProcess->isSuccessful()) {
            $this->notify(Status::FAILED);

            return Error::parse($codyGenerateCommitMessageProcess->getErrorOutput());
        }

        $commitMessage = $codyGenerateCommitMessageProcess->getOutput();
        if ($commitMessage === '' || $commitMessage === '0') {
            $this->notify(Status::FAILED);

            return Error::parse('Unable to generate commit message.');
        }

        $this->notify(Status::FINISHED);

        return $commitMessage;
    }

    private function checkCodyIsInstalled(): bool
    {
        $checkCodyIsInstalledProcess = new Process(['which', 'cody']);
        $checkCodyIsInstalledProcess->run();

        return $checkCodyIsInstalledProcess->isSuccessful();
    }
}
