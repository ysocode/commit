<?php

declare(strict_types=1);

namespace YSOCode\Commit\Foundation\Support;

final class Config
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    public function load(): void
    {
        $config = [];
        $configPath = __DIR__.'/../../../config';

        if (is_dir($configPath)) {
            foreach (scandir($configPath) ?: [] as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $name = pathinfo($file, PATHINFO_FILENAME);
                    $config[$name] = require "{$configPath}/{$file}";
                }
            }
        }

        $this->config = $config;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = self::resolveNestedKey($key);

        return $value ?? $default;
    }

    private function resolveNestedKey(string $key): mixed
    {
        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
