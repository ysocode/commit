<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Console\Commands\Interfaces\GenerateCommitMessageInterface;
use YSOCode\Commit\Application\Console\Commands\Interfaces\GetApiKeyInterface;
use YSOCode\Commit\Application\Services\Factories\AiProviderServiceFactory;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Enums\Status;
use YSOCode\Commit\Domain\Traits\WithObserverToolsTrait;
use YSOCode\Commit\Domain\Types\Error;

class GenerateCommitMessageWithAiProvider implements GenerateCommitMessageInterface
{
    use WithObserverToolsTrait;

    public function __construct(private readonly GetApiKeyInterface $getApiKey) {}

    public function execute(AiProvider $aiProvider, string $prompt, string $diff): string|Error
    {
        $this->notify(Status::STARTED);

        $apiKey = $this->getApiKey->execute($aiProvider);
        if ($apiKey instanceof Error) {
            $this->notify(Status::FAILED);

            return $apiKey;
        }

        $aiProviderService = AiProviderServiceFactory::create($aiProvider, $apiKey);
        if ($aiProviderService instanceof Error) {
            $this->notify(Status::FAILED);

            return $aiProviderService;
        }

        $commitMessage = $aiProviderService->generateCommitMessage($prompt, $diff, function (): void {
            $this->notifyRunningStatus();
        });
        if ($commitMessage instanceof Error) {
            $this->notify(Status::FAILED);

            return $commitMessage;
        }

        $this->notify(Status::FINISHED);

        return $commitMessage;
    }
}
