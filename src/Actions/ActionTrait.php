<?php

namespace YSOCode\Commit\Actions;

use YSOCode\Commit\Domain\Types\Error;

trait ActionTrait
{
    public function getConfigFilePath(): string
    {
        $homeDir = config('app.home_dir');
        $masterDir = config('app.master_dir');
        $configDir = config('app.config_dir');

        return "{$homeDir}/{$masterDir}/{$configDir}/.env";
    }

    public function checkConfigFileExistence(): true|Error
    {
        $configFile = $this->getConfigFilePath();

        if (! file_exists($configFile)) {
            return Error::parse('Unable to locate configuration file');
        }

        return true;
    }
}
