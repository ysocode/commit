<?php

namespace YSOCode\Commit\Foundation\Support;

final class Environment
{
    /** @var array<string, string|int|float|bool|null> */
    private static array $cache = [];

    public static function get(string $key, string|int|float|bool|null $default = null): string|int|float|bool|null
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $value = self::fetchValue($key) ?? $default;

        if (! is_string($value)) {
            return $value;
        }

        return self::$cache[$key] = self::normalizeValue($value);
    }

    private static function fetchValue(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return is_string($value) ? $value : null;
    }

    private static function normalizeValue(string $value): string|int|float|bool|null
    {
        $value = self::removeQuotes($value);

        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => self::convertNumeric($value)
        };
    }

    private static function removeQuotes(string $value): string
    {
        return trim($value, "\"'");
    }

    private static function convertNumeric(string $value): string|int|float
    {
        return is_numeric($value)
            ? (str_contains($value, '.') ? (float) $value : (int) $value)
            : $value;
    }
}
