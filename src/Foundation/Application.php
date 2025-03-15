<?php

declare(strict_types=1);

namespace YSOCode\Commit\Foundation;

use YSOCode\Commit\Foundation\Adapters\Command\CommandManagerInterface;
use YSOCode\Commit\Foundation\Support\Config;
use YSOCode\Commit\Foundation\Support\LocalConfig;

final class Application
{
    private static ?self $instance = null;

    private Config $config;

    private LocalConfig $localConfig;

    private CommandManagerInterface $commandManager;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (! self::$instance instanceof Application) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function setConfig(Config $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function setLocalConfig(LocalConfig $localConfig): self
    {
        $this->localConfig = $localConfig;

        return $this;
    }

    public function getLocalConfig(): LocalConfig
    {
        return $this->localConfig;
    }

    public function setCommandManager(CommandManagerInterface $commandManager): self
    {
        $this->commandManager = $commandManager;

        return $this;
    }

    public function getCommandManager(): CommandManagerInterface
    {
        return $this->commandManager;
    }

    private function load(): void
    {
        $this->config->load();

        $this->localConfig->setConfig($this->config);

        $this->commandManager->load();

    }

    public function boot(): void
    {
        $this->load();

        $this->commandManager->boot();
    }
}
