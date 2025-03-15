<?php

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

return new \YSOCode\Commit\Foundation\Support\Config($config);
