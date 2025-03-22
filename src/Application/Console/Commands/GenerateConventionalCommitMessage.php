<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Commands;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use YSOCode\Commit\Application\Console\Commands\Factories\GenerateCommitMessageFactory;
use YSOCode\Commit\Application\Console\Commands\Interfaces\CommitStagedChangesInterface;
use YSOCode\Commit\Application\Console\Commands\Interfaces\FetchStagedChangesInterface;
use YSOCode\Commit\Application\Console\Commands\Interfaces\GetDefaultAiProviderInterface;
use YSOCode\Commit\Application\Console\Commands\Interfaces\GetDefaultLanguageInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Enums\Language;
use YSOCode\Commit\Domain\Enums\Status;
use YSOCode\Commit\Domain\Types\Error;

class GenerateConventionalCommitMessage extends Command
{
    public function __construct(
        private readonly GetDefaultAiProviderInterface $getDefaultAiProviderFromConfig,
        private readonly GetDefaultLanguageInterface $getDefaultLanguage,
        private readonly FetchStagedChangesInterface $fetchStagedChanges,
        private readonly GenerateCommitMessageFactory $generateCommitMessageFactory,
        private readonly CommitStagedChangesInterface $commitStagedChanges
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $helperMessage = <<<'HELP'
        This command automatically generates a Conventional Commit message based on the provided staged changes.
        It uses AI to analyze the staged changes and create a commit message that adheres to the Conventional Commit standards.
        
        Arguments:
            diff          Provide a custom diff instead of detecting staged changes
        
        Options:
            --provider    Specify which AI provider to use
            --lang        Language of the commit message
        
        Examples:
            commit generate
            commit generate --provider=sourcegraph
            commit generate --lang=pt_BR
            commit generate "your custom diff"
        HELP;

        $this->setName('generate')
            ->setDescription('Generate a conventional commit message using AI based on the provided staged changes')
            ->setHelp($helperMessage)
            ->addArgument('diff', InputArgument::OPTIONAL, 'Provide a custom diff instead of detecting staged changes')
            ->addOption('provider', 'p', InputOption::VALUE_REQUIRED, 'AI provider')
            ->addOption('lang', 'l', InputOption::VALUE_REQUIRED, 'Language of the commit message (e.g., en_US, pt_BR)');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $aiProvider = $this->getAiProvider($input);
        if ($aiProvider instanceof Error) {
            $output->writeln("<error>Error: {$aiProvider}</error>");

            return Command::FAILURE;
        }

        $language = $this->getLanguage($input);
        if ($language instanceof Error) {
            $output->writeln("<error>Error: {$language}</error>");

            return Command::FAILURE;
        }

        $diff = $this->getDiff($input);
        if ($diff instanceof Error) {
            $output->writeln("<error>Error: {$diff}</error>");

            return Command::FAILURE;
        }

        $prompt = <<<PROMPT
        Write a commit message for this diff following Conventional Commits specification.
        ALWAYS wrap the entire commit message between ``` delimiters.
        Do NOT use scopes. 
        EACH line must not exceed 72 characters.
        Write the commit message in {$language->formattedValue()} language without any accents.
        If there are multiple modifications in different contexts, write the body using a list format.
        Otherwise, use a regular paragraph format that ends with a period.
        If the body is a list, DO NOT add a period at the end of each list item, as in the following example:
        ```
        feat: add a new feature
        
        - Add a new feature
        - Fix a bug
        ```
        PROMPT;

        $generateCommitMessage = $this->generateCommitMessageFactory->create(
            $aiProvider,
            $prompt,
            $diff
        );
        if ($generateCommitMessage instanceof Error) {
            $output->writeln("<error>Error: {$generateCommitMessage}</error>");

            return Command::FAILURE;
        }

        $progressIndicator = new ProgressIndicator(
            $output,
            'verbose',
            100,
            ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']
        );

        $generateCommitMessage->subscribe(function (Status $status) use ($progressIndicator, $aiProvider): void {
            match ($status) {
                Status::STARTED => $progressIndicator->start("Processing with {$aiProvider->formattedValue()}..."),
                Status::RUNNING => $progressIndicator->advance(),
                Status::FAILED => $progressIndicator->finish('Failed'),
                Status::FINISHED => $progressIndicator->finish('Finished'),
            };
        });

        $conventionalCommitMessage = $generateCommitMessage->execute();
        if ($conventionalCommitMessage instanceof Error) {
            $output->writeln("<error>Error: {$conventionalCommitMessage}</error>");

            return Command::FAILURE;
        }

        $conventionalCommitMessageFormatted = $this->extractCommitMessage($conventionalCommitMessage);
        if ($conventionalCommitMessageFormatted instanceof Error) {
            $output->writeln("<error>Error: {$conventionalCommitMessageFormatted}</error>");

            return Command::FAILURE;
        }

        $askToConfirmCommitReturn = $this->askToConfirmCommit(
            $input,
            $output,
            $aiProvider,
            $language,
            $conventionalCommitMessageFormatted
        );

        if ($askToConfirmCommitReturn instanceof Error) {
            $output->writeln("<error>Error: {$askToConfirmCommitReturn}</error>");

            return Command::FAILURE;
        }

        if (! $askToConfirmCommitReturn) {
            $output->writeln('<info>Success: No commit made.</info>');

            return Command::SUCCESS;
        }

        $commitStagedChanges = $this->commitStagedChanges->execute($conventionalCommitMessageFormatted);
        if ($commitStagedChanges instanceof Error) {
            $output->writeln("<error>Error: {$commitStagedChanges}</error>");

            return Command::FAILURE;
        }

        $output->writeln('<info>Success: Commit created successfully!</info>');

        return Command::SUCCESS;
    }

    private function getAiProvider(InputInterface $input): AiProvider|Error
    {
        $customAiProvider = $input->getOption('provider');
        if (! is_null($customAiProvider)) {
            if (! $customAiProvider || ! is_string($customAiProvider)) {
                return Error::parse('Invalid AI provider provided.');
            }

            return Aiprovider::parse($customAiProvider);
        }

        return $this->getDefaultAiProviderFromConfig->execute();
    }

    private function getLanguage(InputInterface $input): Language|Error
    {
        $customLanguage = $input->getOption('lang');
        if (! is_null($customLanguage)) {
            if (! $customLanguage || ! is_string($customLanguage)) {
                return Error::parse('Invalid language provided.');
            }

            return Language::parse($customLanguage);
        }

        return $this->getDefaultLanguage->execute();
    }

    private function getDiff(InputInterface $input): string|Error
    {
        $customDiff = $input->getArgument('diff');
        if (! is_null($customDiff)) {
            if (! $customDiff || ! is_string($customDiff)) {
                return Error::parse('Invalid diff format provided.');
            }

            return $customDiff;
        }

        return $this->fetchStagedChanges->execute();
    }

    private function askToConfirmCommit(
        InputInterface $input,
        OutputInterface $output,
        AiProvider $aiProvider,
        Language $language,
        string $conventionalCommitMessage
    ): bool|Error {
        $output->writeln([
            "<info>Below is the generated commit message [AI: {$aiProvider->formattedValue()} | Lang: {$language->formattedValue()}]:</info>",
            '',
            "<fg=yellow>{$conventionalCommitMessage}</fg=yellow>",
            '',
        ]);

        $helper = $this->getHelper('question');
        if (! $helper instanceof QuestionHelper) {
            return Error::parse('Unable to get the question helper.');
        }

        $question = new ConfirmationQuestion(
            '<question>Do you want to create a commit with this message? [Y/n]</question>'
        );

        $askReturn = $helper->ask($input, $output, $question);

        if (! is_bool($askReturn)) {
            return Error::parse('Unable to get the answer.');
        }

        return $askReturn;
    }

    private function extractCommitMessage(string $commitMessage): string|Error
    {
        $pattern = '/```(.*?)```/s';

        if (preg_match($pattern, $commitMessage, $matches)) {
            $commitMessage = trim($matches[1]);

            if ($commitMessage === '' || $commitMessage === '0') {
                return Error::parse('Extracted commit message is empty.');
            }

            return $commitMessage;
        }

        return Error::parse('Unable to extract commit message.');
    }
}
