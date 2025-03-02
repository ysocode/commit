<?php

namespace YSOCode\Commit\Actions;

use YSOCode\Commit\Domain\Error;
use YSOCode\Commit\Enums\AI;
use YSOCode\Commit\Support\EnvFileManager;

readonly class SetAIKey implements Action
{
    public function __construct(
        private AI $ai,
        private string $key,
        private string $masterDir = '.ysocode',
        private string $configDir = 'commit',
    ) {}

    public function execute(): true|Error
    {
        $homeDir = getenv('HOME');

        if (! $homeDir) {
            return Error::parse("Unable to determine the user's home directory");
        }

        $configDir = "{$homeDir}/{$this->masterDir}/{$this->configDir}";
        $configFile = "{$configDir}/.env";
        $stubFile = dirname(__DIR__, 3).'/stubs/.env.stub';

        if (! is_dir($configDir) && ! mkdir($configDir, 0755, true)) {
            return Error::parse('Failed to create configuration directory');
        }

        if (! file_exists($configFile)) {
            if (! file_exists($stubFile)) {
                return Error::parse('Unable to locate configuration file');
            }

            if (! copy($stubFile, $configFile)) {
                return Error::parse('Failed to copy stub file to configuration directory');
            }
        }

        $envName = strtoupper($this->ai->value).'_KEY';

        if (! (new EnvFileManager($configFile))->set($envName, $this->key)->save()) {
            return Error::parse('Failed to update environment variables');
        }

        return true;
    }
}
