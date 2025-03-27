<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Services;

use Closure;
use Symfony\Component\Process\Process;
use YSOCode\Commit\Application\Services\Interface\AiProviderInterface;
use YSOCode\Commit\Application\Services\Types\SourcegraphApiKey;
use YSOCode\Commit\Domain\Types\Error;

readonly class Sourcegraph implements AiProviderInterface
{
    public function __construct(private SourcegraphApiKey $apiKey) {}

    public function generateCommitMessage(string $prompt, string $diff, Closure $onProgress): string|Error
    {
        $codyIsInstalled = $this->checkCodyIsInstalled();
        if (! $codyIsInstalled) {
            return Error::parse('Cody is not installed.');
        }

        $codyProcess = new Process(
            ['cody', 'chat', '--stdin', '-m', $prompt],
            null,
            [
                'SRC_ENDPOINT' => 'https://sourcegraph.com',
                'SRC_ACCESS_TOKEN' => (string) $this->apiKey,
            ],
            $diff
        );

        $codyProcess->start();

        while ($codyProcess->isRunning()) {
            $onProgress();
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

    private function checkCodyIsInstalled(): bool
    {
        $process = new Process(['which', 'cody']);
        $process->run();

        return $process->isSuccessful();
    }
}
