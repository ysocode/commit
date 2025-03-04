<?php

namespace YSOCode\Commit\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'init',
    description: 'Initialize the configuration'
)]
class Init extends Command
{
    protected function configure(): void
    {
        $this->setHelp('This command will initialize the configuration for the application.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $homeDir = config('app.home_dir');
        $masterDir = config('app.master_dir');
        $configDir = config('app.config_dir');

        $configDir = "{$homeDir}/{$masterDir}/{$configDir}";
        $configFile = "{$configDir}/.env";
        $stubFile = dirname(__DIR__, 2).'/stubs/.env.stub';

        if (! is_dir($configDir) && ! mkdir($configDir, 0755, true)) {
            $output->writeln('<error>Error: Failed to create configuration directory</error>');

            return Command::FAILURE;
        }

        if (! file_exists($configFile)) {
            if (! file_exists($stubFile)) {
                $output->writeln('<error>Error: Unable to locate configuration file</error>');

                return Command::FAILURE;
            }

            if (! copy($stubFile, $configFile)) {
                $output->writeln('<error>Error: Failed to copy stub file to configuration directory</error>');

                return Command::FAILURE;
            }
        }

        $output->writeln('<info>Success: Configuration initialized successfully!</info>');

        return Command::SUCCESS;
    }
}
