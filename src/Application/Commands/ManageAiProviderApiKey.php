<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use YSOCode\Commit\Application\Commands\Interfaces\CheckAiProviderIsEnabledInterface;
use YSOCode\Commit\Application\Commands\Interfaces\GetApiKeyInterface;
use YSOCode\Commit\Application\Commands\Interfaces\GetDefaultAiProviderInterface;
use YSOCode\Commit\Application\Commands\Traits\WithCommandToolsTrait;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Domain\Types\Interfaces\ApiKeyInterface;

class ManageAiProviderApiKey extends Command
{
    use WithCommandToolsTrait;

    public function __construct(
        private readonly CheckAiProviderIsEnabledInterface $checkAiProviderIsEnabled,
        private readonly GetDefaultAiProviderInterface $getDefaultAiProvider,
        private readonly GetApiKeyInterface $getApiKey
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $helperMessage = <<<'HELP'
        This command sets, displays, or removes an API key for an AI provider.

        Without --get or --remove, it sets a new API key for the provider.
        If no provider is specified, an interactive prompt will allow selection.

        Arguments:
            api-key       The API key to set (not used with --get or --remove)

        Options:
            --provider    AI provider name
            --get         Display the stored API key for the provider
            --remove      Remove the stored API key for the provider

        Examples:
            commit ai:api-key --provider=openai YOUR_API_KEY
            commit ai:api-key --get --provider=openai
            commit ai:api-key --remove --provider=openai
        HELP;

        $this->setName('ai:api-key')
            ->setDescription('Set, display, or remove an API key for an AI provider')
            ->setHelp($helperMessage)
            ->addArgument('api-key', InputArgument::OPTIONAL, 'API key to set for the provider')
            ->addOption('provider', 'p', InputOption::VALUE_REQUIRED, 'AI provider name')
            ->addOption('get', 'g', InputOption::VALUE_NONE, 'Display the stored API key')
            ->addOption('remove', 'r', InputOption::VALUE_NONE, 'Remove the stored API key');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $get = $this->getBooleanOption($input, 'get');
        if ($get instanceof Error) {
            $output->writeln("<error>Error: {$get}</error>");

            return Command::FAILURE;
        }

        if ($get) {
            $apiKey = $this->handleGetOption($input);
            if ($apiKey instanceof Error) {
                $output->writeln("<error>Error: {$apiKey}</error>");

                return Command::FAILURE;
            }

            $output->writeln("<info>API key: {$apiKey}</info>");

            return Command::SUCCESS;
        }

        return Command::SUCCESS;
    }

    private function handleGetOption(InputInterface $input): ApiKeyInterface|Error
    {
        $apiKeyIsProvided = $this->checkArgumentIsProvided($input, 'api-key');
        if ($apiKeyIsProvided instanceof Error) {
            return $apiKeyIsProvided;
        }

        if ($apiKeyIsProvided) {
            return Error::parse('The "--get" option cannot be used with the "api-key" argument.');
        }

        $aiProvider = $this->getAiProvider($input);
        if ($aiProvider instanceof Error) {
            return $aiProvider;
        }

        return $this->getApiKey->execute($aiProvider);
    }

    private function getAiProvider(InputInterface $input): AiProvider|Error
    {
        $customAiProvider = $input->getOption('provider');
        if (! is_null($customAiProvider)) {
            if (! $customAiProvider || ! is_string($customAiProvider)) {
                return Error::parse('Invalid AI provider provided.');
            }

            $customAiProviderAsEnum = Aiprovider::parse($customAiProvider);
            if ($customAiProviderAsEnum instanceof Error) {
                return $customAiProviderAsEnum;
            }

            $customAiProviderIsEnabled = $this->checkAiProviderIsEnabled->execute($customAiProviderAsEnum);
            if ($customAiProviderIsEnabled instanceof Error) {
                return $customAiProviderIsEnabled;
            }

            if (! $customAiProviderIsEnabled) {
                return Error::parse(
                    sprintf(
                        'The "%s" AI provider is not enabled.',
                        $customAiProviderAsEnum->formattedValue()
                    )
                );
            }

            return $customAiProviderAsEnum;
        }

        return $this->getDefaultAiProvider->execute();
    }
}
