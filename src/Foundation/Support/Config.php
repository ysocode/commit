<?php

namespace YSOCode\Commit\Foundation\Support;

final readonly class Config
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private array $config) {}

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
