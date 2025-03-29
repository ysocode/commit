<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use YSOCode\Commit\Application\Commands\Interfaces\FetchEnabledAiProvidersInterface;
use YSOCode\Commit\Application\Commands\Interfaces\GetDefaultAiProviderInterface;
use YSOCode\Commit\Application\Commands\Interfaces\SetDefaultAiProviderInterface;
use YSOCode\Commit\Application\Commands\Traits\WithCommandToolsTrait;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;

class ManageDefaultAiProvider extends Command
{
    use WithCommandToolsTrait;

    public function __construct(
        private readonly GetDefaultAiProviderInterface $getDefaultAiProvider,
        private readonly SetDefaultAiProviderInterface $setDefaultAiProvider,
        private readonly FetchEnabledAiProvidersInterface $fetchEnabledAiProviders
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $helperMessage = <<<'HELP'
        This command sets or displays the default AI provider for generating commit messages.
        
        Without options, it sets a new default AI provider.
        If no provider argument is specified, it will prompt you to select one.
        
        Arguments:
            provider    AI provider name to set as default (not used with --get)
            
        Options:
            --get       Display the current default AI provider
            --list      Display all enabled AI providers

        Examples:
            ai:provider
            ai:provider --get
            ai:provider --list
            ai:provider sourcegraph
        HELP;

        $this->setName('ai:provider')
            ->setDescription('Set or display the default AI provider for generating commit messages')
            ->setHelp($helperMessage)
            ->addArgument('provider', InputArgument::OPTIONAL, 'AI provider name to set as default')
            ->addOption('get', 'g', InputOption::VALUE_NONE, 'Display the current default AI provider')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'Display all enabled AI providers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $list = $this->getBooleanOption($input, 'list');
        if ($list instanceof Error) {
            $output->writeln("<error>Error: {$list}</error>");

            return Command::FAILURE;
        }

        if ($list) {
            $enabledAiProviders = $this->handleListOption($input);
            if ($enabledAiProviders instanceof Error) {
                $output->writeln("<error>Error: {$enabledAiProviders}</error>");

                return Command::FAILURE;
            }

            $formattedProviders = array_map(
                fn ($aiProvider): string => sprintf('<info>• %s</info>', $aiProvider->getFormattedValue()),
                $enabledAiProviders
            );

            $output->writeln([
                '<comment>Enabled AI Providers:</comment>',
                '',
                ...$formattedProviders,
                '',
            ]);

            return Command::SUCCESS;
        }

        $get = $this->getBooleanOption($input, 'get');
        if ($get instanceof Error) {
            $output->writeln("<error>Error: {$get}</error>");

            return Command::FAILURE;
        }

        if ($get) {
            $defaultAiProvider = $this->handleGetOption($input);
            if ($defaultAiProvider instanceof Error) {
                $output->writeln("<error>Error: {$defaultAiProvider}</error>");

                return Command::FAILURE;
            }

            $output->writeln(
                "The current default AI provider is: {$defaultAiProvider->getFormattedValue()}"
            );

            return Command::SUCCESS;
        }

        $aiProviderIsProvided = $this->checkArgumentIsProvided($input, 'provider');
        if ($aiProviderIsProvided instanceof Error) {
            $output->writeln("<error>Error: {$aiProviderIsProvided}</error>");

            return Command::FAILURE;
        }

        $aiProvider = match ($aiProviderIsProvided) {
            true => $this->getAiProvider($input),
            false => $this->askToChooseDefaultAiProvider($input, $output),
        };

        if ($aiProvider instanceof Error) {
            $output->writeln("<error>Error: {$aiProvider}</error>");

            return Command::FAILURE;
        }

        $defaultAiProviderIsSet = $this->setDefaultAiProvider->execute($aiProvider);
        if ($defaultAiProviderIsSet instanceof Error) {
            $output->writeln("<error>Error: {$defaultAiProviderIsSet}</error>");

            return Command::FAILURE;
        }

        $output->writeln(
            "The default AI provider has been set to: {$aiProvider->getFormattedValue()}"
        );

        return Command::SUCCESS;
    }

    /**
     * @return array<AiProvider>|Error
     */
    private function handleListOption(InputInterface $input): array|Error
    {
        $aiProviderIsProvided = $this->checkArgumentIsProvided($input, 'provider');
        if ($aiProviderIsProvided instanceof Error) {
            return $aiProviderIsProvided;
        }

        if ($aiProviderIsProvided) {
            return Error::parse('The "--list" option cannot be used with the "provider" argument.');
        }

        return $this->fetchEnabledAiProviders->execute();
    }

    private function handleGetOption(InputInterface $input): AiProvider|Error
    {
        $aiProviderIsProvided = $this->checkArgumentIsProvided($input, 'provider');
        if ($aiProviderIsProvided instanceof Error) {
            return $aiProviderIsProvided;
        }

        if ($aiProviderIsProvided) {
            return Error::parse('The "--get" option cannot be used with the "provider" argument.');
        }

        return $this->getDefaultAiProvider->execute();
    }

    private function askToChooseDefaultAiProvider(InputInterface $input, OutputInterface $output): AiProvider|Error
    {
        $fetchEnabledAiProviders = $this->fetchEnabledAiProviders->execute();
        if ($fetchEnabledAiProviders instanceof Error) {
            return $fetchEnabledAiProviders;
        }

        $helper = $this->getHelper('question');
        if (! $helper instanceof QuestionHelper) {
            return Error::parse('Unable to get the question helper.');
        }

        [$firstEnabledAiProvider] = $fetchEnabledAiProviders;

        $question = new ChoiceQuestion(
            "<question>Choose the AI provider to set as default [auto: {$firstEnabledAiProvider->value}]</question>",
            array_map(fn ($aiProvider) => $aiProvider->value, $fetchEnabledAiProviders),
            0
        );

        $questionResponse = $helper->ask($input, $output, $question);
        if (! $questionResponse || ! is_string($questionResponse)) {
            return Error::parse('Unable to get the answer. Please ensure you select a valid AI provider option');
        }

        return AiProvider::parse($questionResponse);
    }

    private function getAiProvider(InputInterface $input): AiProvider|Error
    {
        $aiProvider = $input->getArgument('provider');
        if (! $aiProvider || ! is_string($aiProvider)) {
            return Error::parse('Invalid AI provider argument provided.');
        }

        return Aiprovider::parse($aiProvider);
    }
}
