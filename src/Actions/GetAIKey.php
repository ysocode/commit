<?php

namespace YSOCode\Commit\Actions;

use YSOCode\Commit\Actions\Interfaces\ActionInterface;
use YSOCode\Commit\Actions\Traits\ActionTrait;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Support\EnvFileManager;

readonly class GetAIKey implements ActionInterface
{
    use ActionTrait;

    public function __construct(
        private AI $ai,
    ) {}

    public function execute(): string|Error
    {
        $checkConfigFileExistence = $this->checkConfigFileExistence();
        if ($checkConfigFileExistence instanceof Error) {
            return $checkConfigFileExistence;
        }

        $key = (new EnvFileManager($this->getConfigFilePath()))->get(apiKeyEnvVar($this->ai));

        if (! $key) {
            return Error::parse("No {$this->ai->formattedValue()} API key found");
        }

        return $key;
    }
}
