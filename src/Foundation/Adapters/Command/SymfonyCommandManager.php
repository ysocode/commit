<?php

declare(strict_types=1);

namespace YSOCode\Commit\Foundation\Adapters\Command;

use Exception;
use Symfony\Component\Console\Application as SymfonyConsoleApplication;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface as SymfonyInputInterface;
use Symfony\Component\Console\Output\OutputInterface as SymfonyOutputInterface;
use YSOCode\Commit\Application\Commands\CommandInterface;

class SymfonyCommandManager implements CommandManagerInterface
{
    private readonly SymfonyConsoleApplication $commandManager;

    public function __construct()
    {
        $this->commandManager = new SymfonyConsoleApplication('YSO Code Commit', '3.0.0');
    }

    public function registerCommand(CommandInterface $command): void
    {
        $this->commandManager->add(
            $this->createSymfonyCommand($command)
        );
    }

    public function setDefaultCommand(string $commandName): void
    {
        $this->commandManager->setDefaultCommand($commandName);
    }

    public function load(): void
    {
        $commands = require __DIR__.'/../../../../bootstrap/commands.php';
        if (! is_array($commands)) {
            throw new Exception('Commands not found');
        }

        foreach ($commands as $index => $command) {

            if (! is_string($command)) {
                throw new Exception("Command at index {$index} must be a class name string");
            }

            if (! class_exists($command)) {
                throw new Exception("Command {$command} not found");
            }

            $commandInstance = new $command;
            if (! $commandInstance instanceof CommandInterface) {
                throw new Exception("Command {$command} must implement CommandInterface");
            }

            $this->registerCommand($commandInstance);

            if ($index === 0) {
                $this->setDefaultCommand($commandInstance->getName());
            }
        }
    }

    public function boot(): void
    {
        $this->commandManager->run();
    }

    private function createSymfonyCommand(CommandInterface $command): SymfonyCommand
    {
        return new class($command) extends SymfonyCommand
        {
            public function __construct(private readonly CommandInterface $command)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->setName($this->command->getName());
            }

            protected function execute(SymfonyInputInterface $input, SymfonyOutputInterface $output): int
            {
                return self::SUCCESS;
            }
        };
    }
}
