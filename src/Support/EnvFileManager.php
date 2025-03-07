<?php

namespace YSOCode\Commit\Support;

use YSOCode\Commit\Domain\Types\Error;

class EnvFileManager
{
    /**
     * @var array<string, string>
     */
    private array $envVars = [];

    public function __construct(private readonly string $filePath) {}

    public static function create(string $filePath): self|Error
    {
        $fileManager = new self($filePath);
        $loadReturn = $fileManager->load();

        if ($loadReturn instanceof Error) {
            return $loadReturn;
        }

        return $fileManager;
    }

    private function load(): true|Error
    {
        $content = file_get_contents($this->filePath);
        if (! $content && ! is_string($content)) {
            return Error::parse('Failed to read the .env file');
        }

        $lines = explode(PHP_EOL, $content);

        foreach ($lines as $line) {
            if (! $line || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2) + ['', ''];

            if (! $key) {
                continue;
            }

            $this->envVars[$this->sanitize($key)] = $this->sanitize($value);
        }

        return true;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->envVars[$key] ?? $default;
    }

    public function set(string $key, string $value): self
    {
        $this->envVars[$key] = $value;

        return $this;
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
        return trim(preg_replace('/[\r\n]+/', '', $value) ?? '', " \t\n\r\x0B\"");
    }

    private function quoteIfNeeded(string $value): string
    {
        return preg_match('/[\s=]/', $value) ? '"'.$value.'"' : $value;
    }
}
