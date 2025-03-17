<?php

declare(strict_types=1);

namespace YSOCode\Commit\Foundation\Support;

use YSOCode\Commit\Domain\Types\Error;

final readonly class LocalConfiguration
{
    public function __construct(private Configuration $configuration) {}

    public function getConfigurationDirPath(): string|Error
    {
        $homeDirectory = $this->configuration->getValue('app.home_directory');
        if (! is_string($homeDirectory)) {
            return Error::parse('Unable to locate home directory.');
        }

        $mainDirectory = $this->configuration->getValue('app.main_directory');
        if (! is_string($mainDirectory)) {
            return Error::parse('Unable to locate main directory.');
        }

        $configDirectory = $this->configuration->getValue('app.config_directory');
        if (! is_string($configDirectory)) {
            return Error::parse('Unable to locate config directory.');
        }

        return "{$homeDirectory}/{$mainDirectory}/{$configDirectory}";
    }

    public function getConfigurationFilePath(): string|Error
    {
        $configDirPath = $this->getConfigurationDirPath();
        if ($configDirPath instanceof Error) {
            return $configDirPath;
        }

        return "{$configDirPath}/config.json";
    }

    public function checkConfigurationDirExistence(): true|Error
    {
        $configDir = $this->getConfigurationDirPath();
        if ($configDir instanceof Error) {
            return $configDir;
        }

        if (! is_dir($configDir)) {
            return Error::parse('Unable to locate configuration directory.');
        }

        return true;
    }

    public function checkConfigurationFileExistence(): true|Error
    {
        $configFile = $this->getConfigurationFilePath();
        if ($configFile instanceof Error) {
            return $configFile;
        }

        if (! file_exists($configFile)) {
            return Error::parse('Unable to locate configuration file.');
        }

        return true;
    }

    /**
     * @return string|int|float|bool|array<string, mixed>|Error
     */
    public function getValue(string $key): string|int|float|bool|array|Error
    {
        $configData = $this->getConfigurationFileData();
        if ($configData instanceof Error) {
            return $configData;
        }

        $segments = explode('.', $key);
        $currentValue = $configData;

        foreach ($segments as $segment) {
            if (! is_array($currentValue) || ! array_key_exists($segment, $currentValue)) {
                return Error::parse("Configuration key '{$key}' not found.");
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
        $configData = $this->getConfigurationFileData();
        if ($configData instanceof Error) {
            return $configData;
        }

        $segments = explode('.', $key);

        // Create a reference to traverse the configuration array
        $currentValue = &$configData;

        // Navigate through all segments except the last one
        $lastSegment = array_pop($segments);

        foreach ($segments as $segment) {
            if (! is_array($currentValue) || ! array_key_exists($segment, $currentValue)) {
                return Error::parse("Configuration key '{$key}' not found.");
            }

            // Move the reference deeper into the array
            $currentValue = &$currentValue[$segment];
        }

        // Verify the final array contains the last segment
        if (! is_array($currentValue) || ! array_key_exists($lastSegment, $currentValue)) {
            return Error::parse("Configuration key '{$key}' not found.");
        }

        // Set the new value at the target location
        $currentValue[$lastSegment] = $value;

        return $this->setConfigurationFileData($configData);
    }

    /**
     * @return array<string, string|int|float|bool|array<mixed>>|Error
     */
    private function getConfigurationFileData(): array|Error
    {
        $fileExistence = $this->checkConfigurationFileExistence();
        if ($fileExistence instanceof Error) {
            return $fileExistence;
        }

        $configFile = $this->getConfigurationFilePath();
        if ($configFile instanceof Error) {
            return $configFile;
        }

        $jsonContent = file_get_contents($configFile);
        if ($jsonContent === false) {
            return Error::parse('Unable to read configuration file.');
        }

        $configData = json_decode($jsonContent, true);
        if (! is_array($configData)) {
            return Error::parse('Invalid configuration file format.');
        }

        /** @var array<string, string|int|float|bool|array<mixed>> $configData */
        return $configData;
    }

    /**
     * @param  array<string, string|int|float|bool|array<mixed>>  $configData
     */
    private function setConfigurationFileData(array $configData): true|Error
    {
        $configFile = $this->getConfigurationFilePath();
        if ($configFile instanceof Error) {
            return $configFile;
        }

        $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        $encodedData = json_encode($configData, $options);
        if ($encodedData === false) {
            return Error::parse('Unable to encode configuration data.');
        }

        $result = file_put_contents($configFile, $encodedData);
        if ($result === false) {
            return Error::parse('Unable to write to configuration file.');
        }

        return true;
    }
}
