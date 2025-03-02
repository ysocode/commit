<?php

namespace YSOCode\Commit\Actions;

use YSOCode\Commit\Domain\Error;
use YSOCode\Commit\Enums\AI;
use YSOCode\Commit\Support\EnvFileManager;

readonly class GetAIKey implements Action
{
    public function __construct(
        private AI $ai,
        private string $masterDir = '.ysocode',
        private string $configDir = 'commit',
    ) {}

    public function execute(): string|Error
    {
        $homeDir = getenv('HOME');

        if (! $homeDir) {
            return Error::parse("Unable to determine the user's home directory");
        }

        $configDir = "{$homeDir}/{$this->masterDir}/{$this->configDir}";
        $configFile = "{$configDir}/.env";

        if (! file_exists($configFile)) {
            return Error::parse('Configuration file .env not found at {$configFile}');
        }

        $envName = strtoupper($this->ai->value).'_KEY';

        $key = (new EnvFileManager($configFile))->get($envName);

        if (! $key) {
            return Error::parse("No {$this->ai->formattedValue()} API key found");
        }

        return $key;
    }
}
