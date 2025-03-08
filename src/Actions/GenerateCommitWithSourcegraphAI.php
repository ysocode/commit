<?php

namespace YSOCode\Commit\Actions;

use Closure;
use Symfony\Component\Process\Process;
use YSOCode\Commit\Actions\Interfaces\ActionInterface;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;

readonly class GenerateCommitWithSourcegraphAI implements ActionInterface
{
    public function __construct(
        private string $systemPrompt,
        private AI $ai,
        private string $gitDiff,
        private Closure $onProgress
    ) {}

    public function execute(): string|Error
    {
        $apiKey = $_ENV[apiKeyEnvVar($this->ai)];
        if (! $apiKey || ! is_string($apiKey)) {
            return Error::parse("No {$this->ai->formattedValue()} API key found");
        }

        $command = ['cody', 'chat', '--stdin', '-m', $this->systemPrompt];

        $codyProcess = new Process(
            $command,
            null,
            [
                'SRC_ENDPOINT' => 'https://sourcegraph.com',
                'SRC_ACCESS_TOKEN' => $apiKey,
            ],
            $this->gitDiff
        );

        $codyProcess->start();

        while ($codyProcess->isRunning()) {
            ($this->onProgress)();
        }

        $codyProcess->wait();

        if (! $codyProcess->isSuccessful()) {
            return Error::parse('Unable to retrieve the commit from Git diff');
        }

        return extractCommitMessage($codyProcess->getOutput());
    }
}
