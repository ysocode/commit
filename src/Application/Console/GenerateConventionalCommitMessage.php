<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use YSOCode\Commit\Actions\FetchStagedGitChanges;
use YSOCode\Commit\Actions\GetDefaultAiProviderFromConfig;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\LocalConfiguration;

class GenerateConventionalCommitMessage extends Command
{
    public function __construct(private readonly LocalConfiguration $localConfiguration)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $helperMessage = <<<'HELP'
        This command automatically generates a Conventional Commit message based on the provided Git staged changes.
        It uses AI to analyze the staged changes and create a commit message that adheres to the Conventional Commit standards.
        
        Options:
            --provider    Specify which AI provider to use
            --lang        Language of the commit message
        
        Examples:
            commit
            commit --provider=openai
            commit --lang=pt_BR
            commit --provider=openai --lang=en_US
        HELP;

        $this->setName('generate')
            ->setDescription('Generate a conventional commit message using AI based on the provided Git staged changes')
            ->setHelp($helperMessage)
            ->addOption('provider', 'p', InputOption::VALUE_REQUIRED, 'AI provider')
            ->addOption('lang', 'l', InputOption::VALUE_REQUIRED, 'Language of the commit message (e.g., en_US, pt_BR)')
            ->addOption('diff', 'd', InputOption::VALUE_REQUIRED, 'Provide a custom Git diff instead of detecting staged changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $gitDiff = $this->getDiff($input);
        if ($gitDiff instanceof Error) {
            $output->writeln("<error>Error: {$gitDiff}</error>");

            return Command::FAILURE;
        }

        $aiProvider = $this->getAiProvider($input);
        if ($aiProvider instanceof Error) {
            $output->writeln("<error>Error: {$aiProvider}</error>");

            return Command::FAILURE;
        }

        $output->writeln('<info>Success: Commit created successfully!</info>');

        return Command::SUCCESS;
    }

    private function getDiff(InputInterface $input): string|Error
    {
        $customDiff = $input->getOption('diff');
        if (! is_null($customDiff)) {
            if (! $customDiff || ! is_string($customDiff)) {
                return Error::parse('Invalid diff format provided.');
            }

            return $customDiff;
        }

        return (new FetchStagedGitChanges)->execute();
    }

    private function getAiProvider(InputInterface $input): AiProvider|Error
    {
        $customAiProvider = $input->getOption('provider');
        if (! is_null($customAiProvider)) {
            if (! $customAiProvider || ! is_string($customAiProvider)) {
                return Error::parse('Invalid diff format provided.');
            }

            return Aiprovider::parse($customAiProvider);
        }

        return (new GetDefaultAiProviderFromConfig($this->localConfiguration))->execute();
    }
}
