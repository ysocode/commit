<?php

namespace YSOCode\Commit\Foundation;

use YSOCode\Commit\Foundation\Adapters\Command\CommandManagerInterface;
use YSOCode\Commit\Foundation\Support\Config;
use YSOCode\Commit\Foundation\Support\LocalConfig;

final class Application
{
    private static ?self $instance = null;

    private CommandManagerInterface $commandManager;

    private function __construct(
        private readonly Config $config,
        private readonly LocalConfig $localConfig
    ) {}

    public static function getInstance(Config $config, LocalConfig $localConfig): self
    {
        if (! self::$instance) {
            self::$instance = new self(
                $config,
                $localConfig
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

    public function setCommandManager(CommandManagerInterface $commandManager): void
    {
        $this->commandManager = $commandManager;
    }
}
