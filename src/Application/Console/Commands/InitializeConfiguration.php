<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use YSOCode\Commit\Application\Console\Commands\Interfaces\CreateConfigurationFileInterface;
use YSOCode\Commit\Application\Console\Commands\Traits\WithCommandToolsTrait;
use YSOCode\Commit\Domain\Types\Error;

class InitializeConfiguration extends Command
{
    use WithCommandToolsTrait;

    public function __construct(
        private readonly CreateConfigurationFileInterface $createConfigurationFile
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $helperMessage = <<<'HELP'
        This command initializes the configuration by copying the stub file to the correct directory.
        It sets up the necessary configuration files for the application to work properly.
        
        Examples:
            commit init
            commit init --force
        HELP;

        $this->setName('init')
            ->setDescription('Initialize the configuration by copying the stub file to the correct directory')
            ->setHelp($helperMessage)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force overwrite existing configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $this->getBooleanOption($input, 'force');
        if ($force instanceof Error) {
            $output->writeln("<error>Error: {$force}</error>");

            return Command::FAILURE;
        }

        $userConfigurationFileIsCreated = $this->createConfigurationFile->execute($force);
        if ($userConfigurationFileIsCreated instanceof Error) {
            $output->writeln("<error>Error: {$userConfigurationFileIsCreated}</error>");

            return Command::FAILURE;
        }

        $output->writeln('<info>Success: Configuration initialized!</info>');

        return Command::SUCCESS;
    }
}
