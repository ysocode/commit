<?php

namespace YSOCode\Commit\Commands;

use Dotenv\Dotenv;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;
use YSOCode\Commit\Actions\FetchStagedGitChanges;
use YSOCode\Commit\Actions\GenerateCommitMessageFromGitDiff;
use YSOCode\Commit\Commands\Traits\CommandTrait;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Enums\Lang;
use YSOCode\Commit\Domain\Enums\Status;
use YSOCode\Commit\Domain\Types\Error;

#[AsCommand(
    name: 'generate',
    description: 'Generate a conventional Git commit message using AI based on a Git diff'
)]
class Generate extends Command
{
    use CommandTrait;

    protected function configure(): void
    {
        $helperMessage = <<<'HELP'
        This command automatically generates a Conventional Commit message based on the provided Git diff.
        It uses AI to analyze the changes and create a commit message that adheres to the Conventional Commit standards.
        
        You can customize the AI provider with the --provider option to choose from available AI options.
        Additionally, you can specify the language for the generated commit message using the --lang option (e.g., 'en' for English, 'pt' for Portuguese).
        
        Examples:
            --provider=openai        Specify the AI provider to use.
            --lang=en                Generate the commit message in English.
        HELP;

        $this->setHelp($helperMessage)
            ->addOption('provider', 'p', InputOption::VALUE_REQUIRED, 'AI provider')
            ->addOption('lang', 'l', InputOption::VALUE_REQUIRED, 'Language of the commit message (e.g., en, pt)');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $checkConfigFileExistence = $this->checkConfigFileExistence();
        if ($checkConfigFileExistence instanceof Error) {
            $output->writeln("<error>Error: {$checkConfigFileExistence}</error>");

            return Command::FAILURE;
        }

        Dotenv::createImmutable($this->getConfigDirPath())->load();

        $gitDiff = (new FetchStagedGitChanges)->execute();
        if ($gitDiff instanceof Error) {
            $output->writeln("<error>Error: $gitDiff</error>");

            return Command::FAILURE;
        }

        $aiProvider = $input->getOption('provider');
        if (! $aiProvider || ! is_string($aiProvider) || ! in_array($aiProvider, AI::values())) {
            $aiProvider = $_ENV['SELECTED_AI'];
            if (! $aiProvider || ! is_string($aiProvider)) {
                $output->writeln('<error>Error: Default AI provider has not been selected yet</error>');

                return Command::FAILURE;
            }
        }

        $lang = $input->getOption('lang');
        if (! $lang || ! is_string($lang) || ! in_array($lang, Lang::values())) {
            $lang = 'en';
        }

        $aiProviderAsEnum = AI::from($aiProvider);
        $langAsEnum = Lang::from($lang);

        $progressIndicator = new ProgressIndicator(
            $output,
            'verbose',
            100,
            ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']
        );
        $getCommitFromGitDiff = new GenerateCommitMessageFromGitDiff($aiProviderAsEnum, $langAsEnum, $gitDiff);

        $getCommitFromGitDiff->subscribe(function (Status $status) use ($progressIndicator, $aiProviderAsEnum) {
            match ($status) {
                Status::STARTED => $progressIndicator->start("Processing with {$aiProviderAsEnum->formattedValue()}..."),
                Status::RUNNING => $progressIndicator->advance(),
                Status::FAILED => $progressIndicator->finish('Failed'),
                Status::FINISHED => $progressIndicator->finish('Finished'),
            };
        });

        $commitFromGitDiff = $getCommitFromGitDiff->execute();
        if ($commitFromGitDiff instanceof Error) {
            $output->writeln("<error>Error: {$commitFromGitDiff}</error>");

            return Command::FAILURE;
        }

        $output->writeln([
            '<info>Below is the generated commit:</info>',
            '',
            "<fg=yellow>{$commitFromGitDiff}</fg=yellow>",
            '',
        ]);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion(
            '<question>Do you want to create a commit with this message? [Y/n]</question>'
        );

        if (! $helper->ask($input, $output, $question)) {
            $output->writeln('<info>Success: No commit made</info>');

            return Command::SUCCESS;
        }

        $commitProcess = new Process(['git', 'commit', '-m', $commitFromGitDiff]);
        $commitProcess->run();

        if (! $commitProcess->isSuccessful()) {
            $output->writeln("<error>Error: {$commitProcess->getErrorOutput()}</error>");

            return Command::FAILURE;
        }

        $output->writeln('<info>Success: Commit created successfully!</info>');

        return Command::SUCCESS;
    }
}
