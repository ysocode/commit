<?php

namespace YSOCode\Commit\Actions;

use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Support\EnvFileManager;

readonly class SetAIKey implements ActionInterface
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

        $envName = strtoupper($this->ai->value).'_API_KEY';

        if (! (new EnvFileManager($this->getConfigFilePath()))->set($envName, $this->key)->save()) {
            return Error::parse("Failed to update {$this->ai->formattedValue()} API key");
        }

        return true;
    }
}
