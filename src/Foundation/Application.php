<?php

declare(strict_types=1);

namespace YSOCode\Commit\Foundation;

use Exception;
use YSOCode\Commit\Foundation\Adapters\Command\CommandManagerInterface;
use YSOCode\Commit\Foundation\Support\Config;
use YSOCode\Commit\Foundation\Support\LocalConfig;

final class Application
{
    private static ?self $instance = null;

    private bool $isLoaded = false;

    private function __construct(
        private readonly Config $config,
        private readonly LocalConfig $localConfig,
        private readonly CommandManagerInterface $commandManager
    ) {}

    public static function getInstance(Config $config, LocalConfig $localConfig, CommandManagerInterface $commandManager): self
    {
        if (! self::$instance instanceof \YSOCode\Commit\Foundation\Application) {
            self::$instance = new self(
                $config,
                $localConfig,
                $commandManager
            );
        }

        return self::$instance;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getLocalConfig(): LocalConfig
    {
        return $this->localConfig;
    }

    public function getCommandManager(): CommandManagerInterface
    {
        return $this->commandManager;
    }

    public function load(): void
    {
        $this->commandManager->load();

        $this->isLoaded = true;
    }

    public function boot(): void
    {
        if (! $this->isLoaded) {
            throw new Exception('Application not loaded');
        }

        $this->commandManager->boot();
    }
}
