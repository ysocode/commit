<?php

if (! function_exists('config')) {
    function config(string $key, ?string $default = null): ?string
    {
        static $config = null;

        if ($config === null) {
            $config = [];
            $configPath = dirname(__DIR__, 2).'/config';

            if (is_dir($configPath)) {
                foreach (scandir($configPath) ?: [] as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                        $name = pathinfo($file, PATHINFO_FILENAME);
                        $config[$name] = require "{$configPath}/{$file}";
                    }
                }
            }
        }

        $segments = explode('.', $key);
        $currentValue = $config;

        foreach ($segments as $segment) {
            if (! is_array($currentValue) || ! array_key_exists($segment, $currentValue)) {
                return $default;
            }
            $currentValue = $currentValue[$segment];
        }

        return is_string($currentValue) ? $currentValue : $default;
    }
}
