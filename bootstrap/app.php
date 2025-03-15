<?php

use YSOCode\Commit\Foundation\Adapters\Command\SymfonyCommandManager;
use YSOCode\Commit\Foundation\Application;
use YSOCode\Commit\Foundation\Support\LocalConfig;

$config = require_once __DIR__.'/config.php';

$application = Application::getInstance()
    ->setConfig($config)
    ->setLocalConfig(new LocalConfig($config))
    ->setCommandManager(new SymfonyCommandManager);
