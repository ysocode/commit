<?php

use YSOCode\Commit\Foundation\Support\Environment;

if (! function_exists('env')) {
    function env(string $key, string|int|float|bool|null $default = null): string|int|float|bool|null
    {
        return Environment::get($key, $default);
    }
}
