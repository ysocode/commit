<?php

declare(strict_types=1);

namespace YSOCode\Commit\Foundation\Support;

use YSOCode\Commit\Domain\Types\Error;

final readonly class UserConfiguration
{
    public function __construct(private Configuration $configuration) {}

    public function getUserConfigurationDirPath(): string|Error
    {
        $homeDirectory = $this->configuration->getValue('app.home_directory');
        if (! is_string($homeDirectory)) {
            return Error::parse('Unable to locate home directory.');
        }

        $mainDirectory = $this->configuration->getValue('app.main_directory');
        if (! is_string($mainDirectory)) {
            return Error::parse('Unable to locate main directory.');
        }

        $packageDirectory = $this->configuration->getValue('app.package_directory');
        if (! is_string($packageDirectory)) {
            return Error::parse('Unable to locate package directory.');
        }

        return "{$homeDirectory}/{$mainDirectory}/{$packageDirectory}";
    }

    public function getUserConfigurationFilePath(): string|Error
    {
        $userConfigurationDirPath = $this->getUserConfigurationDirPath();
        if ($userConfigurationDirPath instanceof Error) {
            return $userConfigurationDirPath;
        }

        return "{$userConfigurationDirPath}/config.json";
    }

    public function checkUserConfigurationDirExistence(): true|Error
    {
        $userConfigurationDirPath = $this->getUserConfigurationDirPath();
        if ($userConfigurationDirPath instanceof Error) {
            return $userConfigurationDirPath;
        }

        if (! is_dir($userConfigurationDirPath)) {
            return Error::parse('Unable to locate user configuration directory.');
        }

        return true;
    }

    public function checkUserConfigurationFileExistence(): true|Error
    {
        $userConfigurationFilePath = $this->getUserConfigurationFilePath();
        if ($userConfigurationFilePath instanceof Error) {
            return $userConfigurationFilePath;
        }

        if (! file_exists($userConfigurationFilePath)) {
            return Error::parse('Unable to locate user configuration file.');
        }

        return true;
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
                return Error::parse("User configuration key '{$key}' not found.");
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

        // Navigate through all segments except the last one
        $lastSegment = array_pop($segments);

        foreach ($segments as $segment) {
            if (! is_array($currentValue) || ! array_key_exists($segment, $currentValue)) {
                return Error::parse("User configuration key '{$key}' not found.");
            }

            // Move the reference deeper into the array
            $currentValue = &$currentValue[$segment];
        }

        // Verify the final array contains the last segment
        if (! is_array($currentValue) || ! array_key_exists($lastSegment, $currentValue)) {
            return Error::parse("User configuration key '{$key}' not found.");
        }

        // Set the new value at the target location
        $currentValue[$lastSegment] = $value;

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

        $userConfigurationFilePath = $this->getUserConfigurationFilePath();
        if ($userConfigurationFilePath instanceof Error) {
            return $userConfigurationFilePath;
        }

        $userConfigurationDataAsJson = file_get_contents($userConfigurationFilePath);
        if ($userConfigurationDataAsJson === false) {
            return Error::parse('Unable to read user configuration file.');
        }

        $userConfigurationData = json_decode($userConfigurationDataAsJson, true);
        if (! is_array($userConfigurationData)) {
            return Error::parse('Invalid user configuration file format.');
        }

        /** @var array<string, string|int|float|bool|array<mixed>> $userConfigurationData */
        return $userConfigurationData;
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

        $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        $userConfigurationFileDataEncodedAsJson = json_encode($userConfigurationData, $options);
        if ($userConfigurationFileDataEncodedAsJson === false) {
            return Error::parse('Unable to encode user configuration data.');
        }

        $result = file_put_contents($userConfigurationFilePath, $userConfigurationFileDataEncodedAsJson);
        if ($result === false) {
            return Error::parse('Unable to write to user configuration file.');
        }

        return true;
    }
}
