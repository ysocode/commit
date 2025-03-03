<?php

namespace YSOCode\Commit\Actions;

use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Support\EnvFileManager;

readonly class SetAIKey implements ActionInterface
{
    public function __construct(
        private AI $ai,
        private string $key,
    ) {}

    public function execute(): true|Error
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

        if (! (new EnvFileManager($configFile))->set($envName, $this->key)->save()) {
            return Error::parse("Failed to update environment variables for {$this->ai->formattedValue()}");
        }

        return true;
    }
}
