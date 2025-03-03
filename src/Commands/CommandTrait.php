<?php

namespace YSOCode\Commit\Commands;

use YSOCode\Commit\Domain\Types\Error;

trait CommandTrait
{
    public function getConfigDirPath(): string
    {
        $homeDir = config('app.home_dir');
        $masterDir = config('app.master_dir');
        $configDir = config('app.config_dir');

        return "{$homeDir}/{$masterDir}/{$configDir}";
    }

    public function getConfigFilePath(): string
    {
        return "{$this->getConfigDirPath()}/.env";
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
