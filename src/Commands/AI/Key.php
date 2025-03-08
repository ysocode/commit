<?php

namespace YSOCode\Commit\Commands\AI;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use YSOCode\Commit\Actions\RetrieveAIProviderKey;
use YSOCode\Commit\Actions\SetAIKey;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;

#[AsCommand(
    name: 'ai:key',
    description: 'Manage your AI provider API key'
)]
class Key extends Command
{
    protected function configure(): void
    {
        $helperMessage = <<<'HELP'
        This command allows you to change the API key for your AI provider.
        
        - To set a key, run the command with the "--set" option.
        - To get the current key, run the command with the "--get" option.
        - You can optionally specify a provider with the "--provider" option.
        If not specified, an interactive selection will appear showing available provider.

        Examples:
        commit ai:key --set <your-api-key>              # Will prompt for provider selection
        commit ai:key --set <your-api-key> --provider=openai
        commit ai:key --get                            # Will prompt for provider selection
        commit ai:key --get --provider=cohere
        HELP;

        $this
            ->setHelp($helperMessage)
            ->addOption('set', 's', InputOption::VALUE_NONE, 'Set the key')
            ->addOption('get', 'g', InputOption::VALUE_NONE, 'Get the key')
            ->addOption('provider', 'p', InputOption::VALUE_REQUIRED, 'AI provider')
            ->addArgument('key', InputArgument::OPTIONAL, 'The API key value');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('set') && ! $input->getOption('get')) {
            return $this->setKey($input, $output);
        }

        if ($input->getOption('get') && ! $input->getOption('set')) {
            return $this->getKey($input, $output);
        }

        $output->writeln(<<<'MESSAGE'
        <error>Error: You must specify either "--set" to set the key or "--get" to retrieve it</error>
        MESSAGE);

        return Command::FAILURE;
    }

    public function setKey(InputInterface $input, OutputInterface $output): int
    {
        $key = $input->getArgument('key');
        if (! $key || ! is_string($key)) {
            $output->writeln('<error>Error: No API key provided</error>');

            return Command::FAILURE;
        }

        $aiProvider = $input->getOption('provider');
        if (! $aiProvider || ! is_string($aiProvider)) {
            $aiProvider = $this->choiceQuestionAIProvider($input, $output);
            if ($aiProvider instanceof Error) {
                $output->writeln("<error>Error: {$aiProvider}</error>");

                return Command::FAILURE;
            }
        }

        $aiProviderAsEnum = AI::from($aiProvider);

        $apiKeyIsSet = (new SetAIKey($aiProviderAsEnum, $key))->execute();
        if ($apiKeyIsSet instanceof Error) {
            $output->writeln("<error>Error: {$apiKeyIsSet}</error>");

            return Command::FAILURE;
        }

        $output->writeln("<info>Success: {$aiProviderAsEnum->formattedValue()} API key has been saved</info>");

        return Command::SUCCESS;
    }

    public function getKey(InputInterface $input, OutputInterface $output): int
    {
        $aiProvider = $input->getOption('provider');
        if (! $aiProvider || ! is_string($aiProvider)) {
            $aiProvider = $this->choiceQuestionAIProvider($input, $output);
            if ($aiProvider instanceof Error) {
                $output->writeln("<error>Error: {$aiProvider}</error>");

                return Command::FAILURE;
            }
        }

        $aiProviderAsEnum = AI::from($aiProvider);

        $apiKey = (new RetrieveAIProviderKey($aiProviderAsEnum))->execute();
        if ($apiKey instanceof Error) {
            $output->writeln("<error>Error: $apiKey</error>");

            return Command::FAILURE;
        }

        $output->writeln("<info>Success: Your {$aiProviderAsEnum->formattedValue()} API key is: {$apiKey}</info>");

        return Command::SUCCESS;
    }

    public function choiceQuestionAIProvider(InputInterface $input, OutputInterface $output): string|Error
    {
        $aiList = AI::values();

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion(
            "<question>Select your AI provider (defaults to {$aiList[0]})</question>",
            $aiList,
            0
        );

        $questionResponse = $helper->ask($input, $output, $question);
        if (! $questionResponse || ! is_string($questionResponse) || ! in_array($questionResponse, $aiList)) {
            return Error::parse('Invalid response received. Please ensure you select a valid AI option');
        }

        return $questionResponse;
    }
}
