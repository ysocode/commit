<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use YSOCode\Commit\Application\Console\Commands\Interfaces\FetchEnabledAiProvidersInterface;
use YSOCode\Commit\Application\Console\Commands\Interfaces\GetDefaultAiProviderInterface;
use YSOCode\Commit\Application\Console\Commands\Interfaces\SetDefaultAiProviderInterface;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;

class ManageDefaultAiProvider extends Command
{
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
            --list      Display all available AI providers

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
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'Display all available AI providers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $get = $this->getBooleanOption($input, 'get');
        if ($get instanceof Error) {
            $output->writeln("<error>Error: {$get}</error>");

            return Command::FAILURE;
        }

        if ($get) {
            if ($this->checkAiProviderIsProvided($input)) {
                $output->writeln(
                    '<error>Error: The "--get" option cannot be used with the "provider" argument.</error>'
                );

                return Command::FAILURE;
            }

            $getDefaultAiProviderReturn = $this->getDefaultAiProvider();
            if ($getDefaultAiProviderReturn instanceof Error) {
                $output->writeln("<error>Error: {$getDefaultAiProviderReturn}</error>");

                return Command::FAILURE;
            }

            $output->writeln(
                "The current default AI provider is: {$getDefaultAiProviderReturn->formattedValue()}"
            );

            return Command::SUCCESS;
        }

        $aiProvider = match ($this->checkAiProviderIsProvided($input)) {
            true => $this->getAiProvider($input),
            false => $this->askToChooseDefaultAiProvider($input, $output),
        };

        if ($aiProvider instanceof Error) {
            $output->writeln("<error>Error: {$aiProvider}</error>");

            return Command::FAILURE;
        }

        $setDefaultAiProviderReturn = $this->setDefaultAiProvider($aiProvider);
        if ($setDefaultAiProviderReturn instanceof Error) {
            $output->writeln("<error>Error: {$setDefaultAiProviderReturn}</error>");

            return Command::FAILURE;
        }

        $output->writeln(
            "The default AI provider has been set to: {$aiProvider->formattedValue()}"
        );

        return Command::SUCCESS;
    }

    private function getBooleanOption(InputInterface $input, string $option): bool|Error
    {
        $value = $input->getOption($option);
        if (! is_bool($value)) {
            return Error::parse(
                sprintf('<error>Invalid "--%s" option provided.</error>', $option)
            );
        }

        return $value;
    }

    private function checkAiProviderIsProvided(InputInterface $input): bool
    {
        return ! is_null($input->getArgument('provider'));
    }

    private function getDefaultAiProvider(): AiProvider|Error
    {
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

    private function setDefaultAiProvider(AiProvider $aiProvider): true|Error
    {
        return $this->setDefaultAiProvider->execute($aiProvider);
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
