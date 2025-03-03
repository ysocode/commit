<?php

namespace YSOCode\Commit\Commands\Cohere;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use YSOCode\Commit\Actions\GetAIKey;
use YSOCode\Commit\Actions\SetAIKey;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;

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
        if ($input->getOption('set') && ! $input->getOption('get')) {
            return $this->setKey($input, $output);
        }

        if ($input->getOption('get') && ! $input->getOption('set')) {
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

        if (! $key || ! is_string($key)) {
            $output->writeln('<error>Error: No API key provided. Use --set <your-api-key></error>');

            return Command::FAILURE;
        }

        $actionResponse = (new SetAIKey(AI::COHERE, $key))->execute();
        if ($actionResponse instanceof Error) {
            $output->writeln("<error>Error: $actionResponse</error>");

            return Command::FAILURE;
        }

        $output->writeln('<info>Success: Cohere API key has been saved</info>');

        return Command::SUCCESS;
    }

    private function getKey(OutputInterface $output): int
    {
        $actionResponse = (new GetAIKey(AI::COHERE))->execute();
        if ($actionResponse instanceof Error) {
            $output->writeln("<error>Error: $actionResponse</error>");

            return Command::FAILURE;
        }

        $output->writeln("<info>Your Cohere API key is: {$actionResponse}</info>");

        return Command::SUCCESS;
    }
}
