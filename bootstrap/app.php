<?php

use YSOCode\Commit\Foundation\Adapters\Command\SymfonyCommandManager;
use YSOCode\Commit\Foundation\Application;
use YSOCode\Commit\Foundation\Support\Config;
use YSOCode\Commit\Foundation\Support\LocalConfig;

$application = Application::getInstance()
    ->setLocalConfig(new LocalConfig)
    ->setConfig(new Config)
    ->setCommandManager(new SymfonyCommandManager);
