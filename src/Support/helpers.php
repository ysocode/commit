<?php

use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;

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

if (! function_exists('hasOnlyStringKeys')) {
    /**
     * @param  array<mixed>  $array
     */
    function hasOnlyStringKeys(array $array): bool
    {
        return array_keys($array) === array_filter(array_keys($array), 'is_string');
    }
}

if (! function_exists('apiKeyEnvVar')) {
    function apiKeyEnvVar(AI $ai): string
    {
        return strtoupper("{$ai->value}_API_KEY");
    }
}

if (! function_exists('extractCommitMessage')) {
    function extractCommitMessage(string $commitMessage): string|Error
    {
        $pattern = '/```(.*?)```/s';

        if (preg_match($pattern, $commitMessage, $matches)) {
            $commitMessage = trim($matches[1]);

            if (empty($commitMessage)) {
                return Error::parse('Extracted commit message is empty');
            }

            return $commitMessage;
        }

        return Error::parse('Unable to extract commit message');
    }
}
