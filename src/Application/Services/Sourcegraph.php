<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Services;

use Closure;
use Symfony\Component\Process\Process;
use YSOCode\Commit\Application\Services\Interfaces\AiProviderServiceInterface;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Domain\Types\SourcegraphApiKey;

readonly class Sourcegraph implements AiProviderServiceInterface
{
    public function __construct(
        private SourcegraphApiKey $apiKey,
        private string $model
    ) {}

    public function generateCommitMessage(string $prompt, string $diff, Closure $onProgress): string|Error
    {
        $codyIsInstalled = $this->checkCodyIsInstalled();
        if (! $codyIsInstalled) {
            return Error::parse('Cody is not installed.');
        }

        $codyGenerateCommitMessageProcess = new Process(
            ['cody', 'chat', '--stdin', '-m', $prompt, '--model', $this->model],
            null,
            [
                'SRC_ENDPOINT' => 'https://sourcegraph.com',
                'SRC_ACCESS_TOKEN' => (string) $this->apiKey,
            ],
            $diff
        );

        $codyGenerateCommitMessageProcess->start();

        while ($codyGenerateCommitMessageProcess->isRunning()) {
            $onProgress();
        }

        if (! $codyGenerateCommitMessageProcess->isSuccessful()) {
            return Error::parse($codyGenerateCommitMessageProcess->getErrorOutput());
        }

        $commitMessage = $codyGenerateCommitMessageProcess->getOutput();
        if ($commitMessage === '' || $commitMessage === '0') {
            return Error::parse('Unable to generate commit message.');
        }

        return $commitMessage;
    }

    private function checkCodyIsInstalled(): bool
    {
        $checkCodyIsInstalledProcess = new Process(['which', 'cody']);
        $checkCodyIsInstalledProcess->run();

        return $checkCodyIsInstalledProcess->isSuccessful();
    }
}
