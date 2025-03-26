<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Application\Console\Commands\Interfaces\CreateConfigurationFileInterface;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class CreateUserConfigurationFile implements CreateConfigurationFileInterface
{
    public function __construct(private UserConfiguration $userConfiguration) {}

    public function execute(bool $force = false): true|Error
    {
        $userConfigurationDirExists = $this->userConfiguration->checkUserConfigurationDirExistence();
        if ($userConfigurationDirExists instanceof Error) {
            return $userConfigurationDirExists;
        }

        if (! $userConfigurationDirExists) {
            $userConfigurationDirPath = $this->userConfiguration->getUserConfigurationDirPath();
            if ($userConfigurationDirPath instanceof Error) {
                return $userConfigurationDirPath;
            }

            if (! mkdir($userConfigurationDirPath, 0755, true)) {
                return Error::parse('Failed to create user configuration directory.');
            }
        }

        $userConfigurationFileExists = $this->userConfiguration->checkUserConfigurationFileExistence();
        if ($userConfigurationFileExists instanceof Error) {
            return $userConfigurationFileExists;
        }

        if ($userConfigurationFileExists && ! $force) {
            return Error::parse('User configuration file already exists.');
        }

        $stubFile = basePath('stubs/config.json.stub');
        if (! file_exists($stubFile)) {
            return Error::parse('Unable to locate user configuration stub file.');
        }

        $userConfigurationFilePath = $this->userConfiguration->getUserConfigurationFilePath();
        if ($userConfigurationFilePath instanceof Error) {
            return $userConfigurationFilePath;
        }

        if (! copy($stubFile, $userConfigurationFilePath)) {
            return Error::parse('Failed to copy stub file to user configuration directory.');
        }

        return true;
    }
}
