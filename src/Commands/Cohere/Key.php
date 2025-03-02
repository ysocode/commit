<?php

namespace YSOCode\Commit\Commands\Cohere;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use YSOCode\Commit\Support\EnvFileManager;

#[AsCommand(
    name: 'cohere:key',
    description: 'Manage your Cohere API key'
)]
class Key extends Command
{
    protected function configure(): void
    {
        $helperMessage = <<<'HELP'
        This command allows you to set or get the Cohere API key.
        
        - To set the key, run the command with the "--set" option followed by the key value.
        - To get the current key, run the command with the "--get" option.

        Example to set the key:
        commit cohere:key --set <your-api-key-here>
        
        Example to get the key:
        commit cohere:key --get
        HELP;

        $this
            ->setHelp($helperMessage)
            ->addOption('set', 's', InputOption::VALUE_NONE, 'Set the key')
            ->addOption('get', 'g', InputOption::VALUE_NONE, 'Get the key')
            ->addArgument('key', InputArgument::OPTIONAL, 'The Cohere API key');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('set')) {
            return $this->setKey($input, $output);
        }

        if ($input->getOption('get')) {
            return $this->getKey($output);
        }

        $output->writeln(<<<'MESSAGE'
        <error>Error: You must specify either "--set" to set the key or "--get" to retrieve it</error>
        MESSAGE);

        return Command::FAILURE;
    }

    private function setKey(InputInterface $input, OutputInterface $output): int
    {
        $key = $input->getArgument('key');

        if (! $key) {
            $output->writeln('<error>Error: No API key provided. Use --set <your-api-key></error>');

            return Command::FAILURE;
        }

        $homeDir = env('HOME');

        if (! $homeDir) {
            $output->writeln("<error>Error: Unable to determine the user's home directory</error>");

            return Command::FAILURE;
        }

        $configDir = "{$homeDir}/.ysocode/commit";
        $configFile = "{$configDir}/.env";
        $stubFile = dirname(__DIR__, 3).'/stubs/.env.stub';

        if (! is_dir($configDir) && ! mkdir($configDir, 0755, true)) {
            $output->writeln('<error>Error: Failed to create config directory</error>');

            return Command::FAILURE;
        }

        if (! file_exists($configFile)) {
            if (! file_exists($stubFile)) {
                $output->writeln("<error>Error: Stub file not found at {$stubFile}</error>");

                return Command::FAILURE;
            }

            if (! copy($stubFile, $configFile)) {
                $output->writeln('<error>Error: Failed to copy stub file to configuration directory</error>');

                return Command::FAILURE;
            }
        }

        $envManager = new EnvFileManager($configFile);
        $envManager->set('COHERE_KEY', $key);
        $envManager->save();

        $output->writeln('<info>Success: Cohere API key has been saved</info>');

        return Command::SUCCESS;
    }

    private function getKey(OutputInterface $output): int
    {
        $homeDir = env('HOME');

        if (! $homeDir) {
            $output->writeln("<error>Error: Unable to determine the user's home directory</error>");

            return Command::FAILURE;
        }

        $configDir = "{$homeDir}/.ysocode/commit";
        $configFile = "{$configDir}/.env";

        if (! file_exists($configFile)) {
            $output->writeln("<error>Error: Configuration file .env not found at {$configFile}</error>");

            return Command::FAILURE;
        }

        $envManager = new EnvFileManager($configFile);
        $key = $envManager->get('COHERE_KEY');

        if (! $key) {
            $output->writeln('<error>Error: No Cohere API key found</error>');

            return Command::FAILURE;
        }

        $output->writeln("<info>Your Cohere API key is: {$key}</info>");

        return Command::SUCCESS;
    }
}
