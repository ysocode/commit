<?php

declare(strict_types=1);

namespace YSOCode\Commit\Foundation\Adapters\Command;

use YSOCode\Commit\Application\Commands\CommandInterface;

interface CommandManagerInterface
{
    public function registerCommand(CommandInterface $command): void;

    public function setDefaultCommand(string $commandName): void;

    public function load(): void;

    public function boot(): void;
}
