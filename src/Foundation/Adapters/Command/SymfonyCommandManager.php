<?php

declare(strict_types=1);

namespace YSOCode\Commit\Foundation\Adapters\Command;

use YSOCode\Commit\Application\Commands\CommandInterface;

class SymfonyCommandManager implements CommandManagerInterface
{
    public function registerCommand(CommandInterface $command): void
    {
        // TODO: Implement registerCommand() method.
    }

    public function setDefaultCommand(string $commandName): void
    {
        // TODO: Implement setDefaultCommand() method.
    }

    public function load(): void
    {
        // TODO: Implement load() method.
    }

    public function boot(): void
    {
        // TODO: Implement boot() method.
    }
}
