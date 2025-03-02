<?php

namespace YSOCode\Commit\Commands\OpenAI;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use YSOCode\Commit\Actions\GetAIKey;
use YSOCode\Commit\Actions\SetAIKey;
use YSOCode\Commit\Domain\Error;
use YSOCode\Commit\Enums\AI;

#[AsCommand(
    name: 'openai:key',
    description: 'Manage your OpenAI API key'
)]
class Key extends Command
{
    protected function configure(): void
    {
        $helperMessage = <<<'HELP'
        This command allows you to set or get the OpenAI API key.
        
        - To set the key, run the command with the "--set" option followed by the key value.
        - To get the current key, run the command with the "--get" option.

        Example to set the key:
        commit openai:key --set <your-api-key-here>
        
        Example to get the key:
        commit openai:key --get
        HELP;

        $this
            ->setHelp($helperMessage)
            ->addOption('set', 's', InputOption::VALUE_NONE, 'Set the key')
            ->addOption('get', 'g', InputOption::VALUE_NONE, 'Get the key')
            ->addArgument('key', InputArgument::OPTIONAL, 'The OpenAI API key');
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

        if (! $key || ! is_string($key)) {
            $output->writeln('<error>Error: No API key provided. Use --set <your-api-key></error>');

            return Command::FAILURE;
        }

        $actionResponse = (new SetAIKey(AI::OPENAI, $key))->execute();
        if ($actionResponse instanceof Error) {
            $output->writeln("<error>Error: $actionResponse</error>");

            return Command::FAILURE;
        }

        $output->writeln('<info>Success: OpenAI API key has been saved</info>');

        return Command::SUCCESS;
    }

    private function getKey(OutputInterface $output): int
    {
        $actionResponse = (new GetAIKey(AI::OPENAI))->execute();
        if ($actionResponse instanceof Error) {
            $output->writeln("<error>Error: $actionResponse</error>");

            return Command::FAILURE;
        }

        $output->writeln("<info>Your OpenAI API key is: {$actionResponse}</info>");

        return Command::SUCCESS;
    }
}
