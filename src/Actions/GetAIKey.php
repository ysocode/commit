<?php

namespace YSOCode\Commit\Actions;

use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Support\EnvFileManager;

readonly class GetAIKey implements ActionInterface
{
    public function __construct(
        private AI $ai,
    ) {}

    public function execute(): string|Error
    {
        $homeDir = config('app.home_dir');
        $masterDir = config('app.master_dir');
        $configDir = config('app.config_dir');

        $configDir = "{$homeDir}/{$masterDir}/{$configDir}";
        $configFile = "{$configDir}/.env";

        if (! file_exists($configFile)) {
            return Error::parse('Unable to locate configuration file');
        }

        $envName = strtoupper($this->ai->value).'_KEY';

        $key = (new EnvFileManager($configFile))->get($envName);

        if (! $key) {
            return Error::parse("No {$this->ai->formattedValue()} API key found");
        }

        return $key;
    }
}
