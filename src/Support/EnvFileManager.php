<?php

namespace YSOCode\Commit\Support;

class EnvFileManager
{
    private array $envVars = [];

    public function __construct(private readonly string $filePath)
    {
        if (file_exists($this->filePath)) {
            $this->loadEnv();
        }
    }

    private function loadEnv(): void
    {
        $content = file_get_contents($this->filePath);
        $lines = explode(PHP_EOL, $content);

        foreach ($lines as $line) {
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $this->envVars[$this->sanitize($key)] = $this->sanitize($value);
        }
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->envVars[$key] ?? $default;
    }

    public function set(string $key, string $value): void
    {
        $this->envVars[$key] = $value;
    }

    public function save(): bool
    {
        $content = '';

        foreach ($this->envVars as $key => $value) {
            $content .= "{$key}=".$this->quoteIfNeeded($value).PHP_EOL;
        }

        return file_put_contents($this->filePath, $content) !== false;
    }

    private function sanitize(string $value): string
    {
        return trim(preg_replace('/[\r\n]+/', '', $value), " \t\n\r\x0B\"");
    }

    private function quoteIfNeeded(string $value): string
    {
        return preg_match('/[\s=]/', $value) ? '"'.$value.'"' : $value;
    }
}
