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
    private array $data;

    /**
     * @param  string|array<string, mixed>  $configurationSourceOrData
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string|array $configurationSourceOrData)
    {
        $this->data = is_array($configurationSourceOrData)
            ? $configurationSourceOrData
            : $this->loadConfigurationFromDirectory($configurationSourceOrData);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    private function loadConfigurationFromDirectory(string $directoryPath): array
    {
        if (! is_dir($directoryPath) || ! is_readable($directoryPath)) {
            throw new InvalidArgumentException(
                sprintf('The configuration directory "%s" does not exist or cannot be read', $directoryPath)
            );
        }

        $data = [];
        $phpFiles = glob($directoryPath.'/*.php') ?: [];

        foreach ($phpFiles as $filePath) {
            $fileName = pathinfo($filePath, PATHINFO_FILENAME);
            $data[$fileName] = require $filePath;
        }

        return $data;
    }

    public function getValue(string $keyPath, mixed $defaultValue = null): mixed
    {
        $value = $this->resolveNestedKeyPath($keyPath);

        if ($value instanceof Error) {
            return $defaultValue;
        }

        return $value;
    }

    private function resolveNestedKeyPath(string $keyPath): mixed
    {
        $segments = explode('.', $keyPath);
        $currentValue = $this->data;

        foreach ($segments as $segment) {
            if (! is_array($currentValue) || ! array_key_exists($segment, $currentValue)) {
                return Error::parse(
                    sprintf('The configuration key "%s" does not exist.', $keyPath)
                );
            }
            $currentValue = $currentValue[$segment];
        }

        return $currentValue;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllData(): array
    {
        return $this->data;
    }
}
