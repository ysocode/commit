<?php

declare(strict_types=1);

namespace YSOCode\Commit\Foundation\Support;

use InvalidArgumentException;
use YSOCode\Commit\Domain\Types\Error;

readonly class Configuration
{
    /**
     * @var array<string, mixed>
     */
    private array $configurationData;

    /**
     * @param  string|array<string, mixed>  $configurationDirPathOrConfigurationData
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string|array $configurationDirPathOrConfigurationData)
    {
        $this->configurationData = is_array($configurationDirPathOrConfigurationData)
            ? $configurationDirPathOrConfigurationData
            : $this->loadConfigurationFromDir($configurationDirPathOrConfigurationData);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    private function loadConfigurationFromDir(string $directoryPath): array
    {
        if (! is_dir($directoryPath) || ! is_readable($directoryPath)) {
            throw new InvalidArgumentException(
                sprintf('The configuration directory "%s" does not exist or cannot be read.', $directoryPath)
            );
        }

        $configurationData = [];
        $phpFiles = glob($directoryPath.'/*.php') ?: [];

        foreach ($phpFiles as $filePath) {
            $fileName = pathinfo($filePath, PATHINFO_FILENAME);
            $configurationData[$fileName] = require $filePath;
        }

        return $configurationData;
    }

    public function getValue(string $keyPath, mixed $defaultValue = null): mixed
    {
        $segments = explode('.', $keyPath);
        $currentValue = $this->configurationData;

        foreach ($segments as $segment) {
            if (! is_array($currentValue) || ! array_key_exists($segment, $currentValue)) {
                return Error::parse(
                    sprintf('The configuration key "%s" does not exist.', $keyPath)
                );
            }
            $currentValue = $currentValue[$segment];
        }

        return $currentValue ?: $defaultValue;
    }
}
