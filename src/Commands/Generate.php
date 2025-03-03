<?php

namespace YSOCode\Commit\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Process\Process;
use YSOCode\Commit\Actions\GetCommitFromGitDiff;
use YSOCode\Commit\Actions\GetGitDiff;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Types\Error;

#[AsCommand(
    name: 'generate',
    description: 'Generate a conventional Git commit message using AI based on a Git diff.'
)]
class Generate extends Command
{
    protected function configure(): void
    {
        $helperMessage = <<<'HELP'
        This command generates a conventional commit message by analyzing the provided Git diff
        and using AI to create a message that adheres to the conventional commit standards.
        HELP;

        $this->setHelp($helperMessage)
            ->addOption('ai', 'a', InputOption::VALUE_OPTIONAL, 'Decide which AI model to use');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $gitDiff = (new GetGitDiff)->execute();
        if ($gitDiff instanceof Error) {
            $output->writeln("<error>Error: $gitDiff</error>");

            return Command::FAILURE;
        }

        $ai = $_ENV['SELECTED_AI'];
        if (! $ai || ! is_string($ai)) {
            $output->writeln(<<<'MESSAGE'
            <error>Error: AI has not been selected yet. To define the AI, use the command: "ai --set"</error>
            MESSAGE);

            return Command::FAILURE;
        }

        $aiAsEnum = AI::from($ai);

        $commitFromGitDiff = new GetCommitFromGitDiff($aiAsEnum, $gitDiff);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion(
            '<question>Do you want to create a commit with this message?</question>',
            ['yes', 'no'],
            0
        );

        if (! $helper->ask($input, $output, $question)) {
            $output->writeln('<info>No commit made</info>');

            return Command::SUCCESS;
        }

        $commitProcess = new Process(['git', 'commit', '-m', $commitFromGitDiff]);
        $commitProcess->run();

        if (! $commitProcess->isSuccessful()) {
            $output->writeln("<error>Error: {$commitProcess->getErrorOutput()}</error>");

            return Command::FAILURE;
        }

        $output->writeln('<info>Commit created successfully!</info>');

        return Command::SUCCESS;
    }
}
