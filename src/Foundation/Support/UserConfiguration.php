<?php

declare(strict_types=1);

namespace YSOCode\Commit\Foundation\Support;

use YSOCode\Commit\Domain\Types\Error;

readonly class UserConfiguration
{
    public function __construct(private Configuration $configuration) {}

    public function getUserConfigurationDirPath(): string|Error
    {
        $homeDirectory = $this->configuration->getValue('app.home_directory', '/tmp');
        if (! $homeDirectory || ! is_string($homeDirectory)) {
            return Error::parse('Unable to locate home directory.');
        }

        $mainDirectory = $this->configuration->getValue('app.main_directory', '/.ysocode');
        if (! $mainDirectory || ! is_string($mainDirectory)) {
            return Error::parse('Unable to locate main directory.');
        }

        $packageDirectory = $this->configuration->getValue('app.package_directory', '/commit');
        if (! $packageDirectory || ! is_string($packageDirectory)) {
            return Error::parse('Unable to locate package directory.');
        }

        return $homeDirectory.$mainDirectory.$packageDirectory;
    }

    public function getUserConfigurationFilePath(): string|Error
    {
        $userConfigurationDirPath = $this->getUserConfigurationDirPath();
        if ($userConfigurationDirPath instanceof Error) {
            return $userConfigurationDirPath;
        }

        return "{$userConfigurationDirPath}/config.json";
    }

    public function checkUserConfigurationDirExistence(): bool|Error
    {
        $userConfigurationDirPath = $this->getUserConfigurationDirPath();
        if ($userConfigurationDirPath instanceof Error) {
            return $userConfigurationDirPath;
        }

        return is_dir($userConfigurationDirPath);
    }

    public function checkUserConfigurationFileExistence(): bool|Error
    {
        $userConfigurationFilePath = $this->getUserConfigurationFilePath();
        if ($userConfigurationFilePath instanceof Error) {
            return $userConfigurationFilePath;
        }

        return file_exists($userConfigurationFilePath);
    }

    /**
     * @return string|int|float|bool|array<string, mixed>|Error
     */
    public function getValue(string $key): string|int|float|bool|array|Error
    {
        $userConfigurationData = $this->getUserConfigurationFileData();
        if ($userConfigurationData instanceof Error) {
            return $userConfigurationData;
        }

        $segments = explode('.', $key);
        $currentValue = $userConfigurationData;

        foreach ($segments as $segment) {
            if (! is_array($currentValue) || ! array_key_exists($segment, $currentValue)) {
                return Error::parse(sprintf('User configuration key "%s" not found.', $key));
            }
            $currentValue = $currentValue[$segment];
        }

        /** @var string|int|float|bool|array<string, mixed> $currentValue */
        return $currentValue;
    }

    /**
     * @param  string|int|float|bool|array<string, mixed>  $value
     */
    public function setValue(string $key, string|int|float|bool|array $value): true|Error
    {
        $userConfigurationData = $this->getUserConfigurationFileData();
        if ($userConfigurationData instanceof Error) {
            return $userConfigurationData;
        }

        $segments = explode('.', $key);

        // Create a reference to traverse the configuration array
        $currentValue = &$userConfigurationData;

        foreach ($segments as $segment) {
            if (! is_array($currentValue) || ! array_key_exists($segment, $currentValue)) {
                return Error::parse(sprintf('User configuration key "%s" not found.', $key));
            }

            // Move the reference deeper into the array
            $currentValue = &$currentValue[$segment];
        }

        // Set the new value at the target location
        $currentValue = $value;

        return $this->setConfigurationFileData($userConfigurationData);
    }

    /**
     * @return array<string, string|int|float|bool|array<mixed>>|Error
     */
    private function getUserConfigurationFileData(): array|Error
    {
        $userConfigurationFileExistence = $this->checkUserConfigurationFileExistence();
        if ($userConfigurationFileExistence instanceof Error) {
            return $userConfigurationFileExistence;
        }

        if (! $userConfigurationFileExistence) {
            return Error::parse('User configuration file does not exist.');
        }

        $userConfigurationFilePath = $this->getUserConfigurationFilePath();
        if ($userConfigurationFilePath instanceof Error) {
            return $userConfigurationFilePath;
        }

        $userConfigurationData = file_get_contents($userConfigurationFilePath);
        if ($userConfigurationData === false) {
            return Error::parse('Unable to read user configuration file.');
        }

        $userConfigurationDataArray = json_decode($userConfigurationData, true);
        if (! is_array($userConfigurationDataArray)) {
            return Error::parse('Invalid user configuration file format.');
        }

        /** @var array<string, string|int|float|bool|array<mixed>> $userConfigurationDataArray */
        return $userConfigurationDataArray;
    }

    /**
     * @param  array<string, string|int|float|bool|array<mixed>>  $userConfigurationData
     */
    private function setConfigurationFileData(array $userConfigurationData): true|Error
    {
        $userConfigurationFilePath = $this->getUserConfigurationFilePath();
        if ($userConfigurationFilePath instanceof Error) {
            return $userConfigurationFilePath;
        }

        $userConfigurationFileDataJson = json_encode(
            $userConfigurationData,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($userConfigurationFileDataJson === false) {
            return Error::parse('Unable to convert user configuration data to JSON.');
        }

        $contentWritten = file_put_contents($userConfigurationFilePath, $userConfigurationFileDataJson);
        if ($contentWritten === false) {
            return Error::parse('Unable to write to user configuration file.');
        }

        return true;
    }
}
