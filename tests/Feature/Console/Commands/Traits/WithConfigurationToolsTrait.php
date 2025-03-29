<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands\Traits;

use Exception;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\Configuration;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

trait WithConfigurationToolsTrait
{
    private static UserConfiguration $userConfiguration;

    private static function setUpUserConfiguration(): void
    {
        self::$userConfiguration = new UserConfiguration(
            new Configuration([
                'app' => [
                    'home_directory' => basePath('storage/tmp'),
                    'main_directory' => '/.ysocode',
                    'package_directory' => '/commit',
                ],
            ])
        );
    }

    /**
     * @throws Exception
     */
    private static function removeUserConfigurationDir(): void
    {
        $userConfigurationDirPath = self::$userConfiguration->getUserConfigurationDirPath();
        if ($userConfigurationDirPath instanceof Error) {
            throw new Exception((string) $userConfigurationDirPath);
        }

        deleteDirectory($userConfigurationDirPath);
    }

    /**
     * @throws Exception
     */
    private static function createUserConfigurationDir(): void
    {
        $userConfigurationDirPath = self::$userConfiguration->getUserConfigurationDirPath();
        if ($userConfigurationDirPath instanceof Error) {
            throw new Exception((string) $userConfigurationDirPath);
        }

        if (! mkdir($userConfigurationDirPath, 0755, true)) {
            throw new Exception('Failed to create user configuration directory.');
        }
    }

    /**
     * @throws Exception
     */
    private static function createUserConfigurationFile(): void
    {
        $userConfigurationDirExists = self::$userConfiguration->checkUserConfigurationDirExistence();
        if ($userConfigurationDirExists instanceof Error) {
            throw new Exception((string) $userConfigurationDirExists);
        }

        if (! $userConfigurationDirExists) {
            self::createUserConfigurationDir();
        }

        $stubFile = basePath('stubs/config.json.stub');
        if (! file_exists($stubFile)) {
            throw new Exception('Unable to locate user configuration stub file.');
        }

        $userConfigurationFilePath = self::$userConfiguration->getUserConfigurationFilePath();
        if ($userConfigurationFilePath instanceof Error) {
            throw new Exception((string) $userConfigurationFilePath);
        }

        if (! copy($stubFile, $userConfigurationFilePath)) {
            throw new Exception('Failed to copy stub file to user configuration directory.');
        }
    }
}
