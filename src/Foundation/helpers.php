<?php

declare(strict_types=1);

use YSOCode\Commit\Foundation\Support\Environment;

if (! function_exists('basePath')) {
    function basePath(string $path = ''): string
    {
        $formattedPath = trim($path, '/');

        return __DIR__."/../../{$formattedPath}";
    }
}

if (! function_exists('deleteDirectory')) {
    function deleteDirectory(string $dir): bool
    {
        if (! is_dir($dir)) {
            return false;
        }

        $files = scandir($dir);

        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }

            $filePath = $dir.'/'.$file;

            if (is_dir($filePath)) {
                return deleteDirectory($filePath);
            }

            unlink($filePath);
        }

        return rmdir($dir);
    }
}

if (! function_exists('env')) {
    function env(string $key, string|int|float|bool|null $default = null): string|int|float|bool|null
    {
        return Environment::get($key, $default);
    }
}
