<?php

namespace YSOCode\Commit\Commands\AI;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use YSOCode\Commit\Commands\Traits\CommandTrait;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Support\EnvFileManager;

#[AsCommand(
    name: 'ai:provider',
    description: 'Manage the AI provider selection'
)]
class Provider extends Command
{
    use CommandTrait;

    protected function configure(): void
    {
        $helperMessage = <<<'HELP'
        This command allows you to manage the AI provider selection.
        Use "--set" to set the AI provider or "--get" to retrieve the current AI provider.
        HELP;

        $this
            ->setHelp($helperMessage)
            ->addOption('set', 's', InputOption::VALUE_NONE, 'Set the AI provider')
            ->addOption('get', 'g', InputOption::VALUE_NONE, 'Get the current AI provider');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('set') && ! $input->getOption('get')) {
            return $this->setAI($input, $output);
        }

        if ($input->getOption('get') && ! $input->getOption('set')) {
            return $this->getAI($output);
        }

        $output->writeln(<<<'MESSAGE'
        <error>
            Error: You must specify either "--set" to set the AI provider or "--get" to retrieve the current AI provider.
        </error>
        MESSAGE);

        return Command::FAILURE;
    }

    private function setAI(InputInterface $input, OutputInterface $output): int
    {
        $checkConfigFileExistence = $this->checkConfigFileExistence();
        if ($checkConfigFileExistence instanceof Error) {
            $output->writeln("<error>Error: {$checkConfigFileExistence}</error>");

            return Command::FAILURE;
        }

        $aiList = AI::values();

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion(
            "<question>Choose the AI to set as default (defaults to {$aiList[0]})</question>",
            $aiList,
            0
        );

        $questionResponse = $helper->ask($input, $output, $question);
        if (! $questionResponse || ! is_string($questionResponse)) {
            $output->writeln(<<<'MESSAGE'
            <error>Error: Invalid response received. Please ensure you select a valid AI option</error>
            MESSAGE);

            return Command::FAILURE;
        }

        $selectedAI = AI::from($questionResponse);

        $envFileManager = EnvFileManager::create($this->getConfigFilePath());

        if ($envFileManager instanceof Error) {
            $output->writeln("<error>Error: {$envFileManager}</error>");

            return Command::FAILURE;
        }

        if (! $envFileManager->set('SELECTED_AI', $selectedAI->value)->save()) {
            $output->writeln(<<<MESSAGE
            <error>Error: Failed to update "SELECTED_AI" environment variables for {$selectedAI->formattedValue()}</error>
            MESSAGE);

            return Command::FAILURE;
        }

        $output->writeln("<info>Success: The default AI has been set to {$selectedAI->formattedValue()}</info>");

        return Command::SUCCESS;
    }

    private function getAI(OutputInterface $output): int
    {
        $checkConfigFileExistence = $this->checkConfigFileExistence();
        if ($checkConfigFileExistence instanceof Error) {
            $output->writeln("<error>Error: {$checkConfigFileExistence}</error>");

            return Command::FAILURE;
        }

        $envFileManager = EnvFileManager::create($this->getConfigFilePath());

        if ($envFileManager instanceof Error) {
            $output->writeln("<error>Error: {$envFileManager}</error>");

            return Command::FAILURE;
        }

        $selectedAI = $envFileManager->get('SELECTED_AI');
        if (! $selectedAI) {
            $output->writeln(<<<'MESSAGE'
            <error>Error: No AI has been selected as the default. Please set a default AI first</error>
            MESSAGE);

            return Command::FAILURE;
        }

        $selectedAIAsEnum = AI::from($selectedAI);

        $output->writeln("<info>Success: The current selected AI is: {$selectedAIAsEnum->formattedValue()}</info>");

        return Command::SUCCESS;
    }
}
