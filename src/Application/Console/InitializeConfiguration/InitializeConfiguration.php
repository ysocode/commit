<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\InitializeConfiguration;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

class InitializeConfiguration extends Command
{
    public function __construct(private readonly UserConfiguration $userConfiguration)
    {
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
        $force = $input->getOption('force');
        if (! is_bool($force)) {
            $output->writeln('<error>Error: Invalid force type provided.</error>');

            return Command::FAILURE;
        }

        $createConfigurationFileReturn = $this->createConfigurationFile($force);
        if ($createConfigurationFileReturn instanceof Error) {
            $output->writeln("<error>Error: {$createConfigurationFileReturn}</error>");

            return Command::FAILURE;
        }

        $output->writeln('<info>Success: Configuration initialized!</info>');

        return Command::SUCCESS;
    }

    private function createConfigurationFile(bool $force = false): true|Error
    {
        if ($this->userConfiguration->checkUserConfigurationDirExistence() instanceof Error) {
            $userConfigurationDirPath = $this->userConfiguration->getUserConfigurationDirPath();
            if ($userConfigurationDirPath instanceof Error) {
                return $userConfigurationDirPath;
            }

            if (! mkdir($userConfigurationDirPath, 0755, true)) {
                return Error::parse('Failed to create user configuration directory.');
            }
        }

        if (! $this->userConfiguration->checkUserConfigurationFileExistence() instanceof Error && ! $force) {
            return Error::parse('User configuration file already exists.');
        }

        $stubFile = __DIR__.'/../../../../stubs/config.json.stub';
        if (! file_exists($stubFile)) {
            return Error::parse('Unable to locate user configuration stub file.');
        }

        $userConfigurationFilePath = $this->userConfiguration->getUserConfigurationFilePath();
        if ($userConfigurationFilePath instanceof Error) {
            return $userConfigurationFilePath;
        }

        if (! copy($stubFile, $userConfigurationFilePath)) {
            return Error::parse('Failed to copy stub file to user configuration directory.');
        }

        return true;
    }
}
