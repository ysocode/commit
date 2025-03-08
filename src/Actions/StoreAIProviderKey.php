<?php

namespace YSOCode\Commit\Actions;

use YSOCode\Commit\Actions\Interfaces\ActionInterface;
use YSOCode\Commit\Actions\Traits\ActionTrait;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Support\EnvFileManager;

readonly class StoreAIProviderKey implements ActionInterface
{
    use ActionTrait;

    public function __construct(
        private AI $ai,
        private string $key,
    ) {}

    public function execute(): true|Error
    {
        $checkConfigFileExistence = $this->checkConfigFileExistence();
        if ($checkConfigFileExistence instanceof Error) {
            return $checkConfigFileExistence;
        }

        $envFileManager = EnvFileManager::create($this->getConfigFilePath());

        if ($envFileManager instanceof Error) {
            return $envFileManager;
        }

        if (! $envFileManager->set(apiKeyEnvVar($this->ai), $this->key)->save()) {
            return Error::parse("Failed to update {$this->ai->formattedValue()} API key");
        }

        return true;
    }
}
