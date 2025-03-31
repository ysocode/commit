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
use YSOCode\Commit\Application\Commands\Interfaces\RemoveApiKeyInterface;
use YSOCode\Commit\Application\Commands\Interfaces\SetApiKeyInterface;
use YSOCode\Commit\Application\Commands\Traits\WithCommandToolsTrait;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Domain\Types\Factories\ApiKeyFactory;
use YSOCode\Commit\Domain\Types\Interfaces\ApiKeyInterface;

class ManageAiProviderApiKey extends Command
{
    use WithCommandToolsTrait;

    public function __construct(
        private readonly CheckAiProviderIsEnabledInterface $checkAiProviderIsEnabled,
        private readonly GetDefaultAiProviderInterface $getDefaultAiProvider,
        private readonly GetApiKeyInterface $getApiKey,
        private readonly RemoveApiKeyInterface $removeApiKey,
        private readonly SetApiKeyInterface $setApiKey
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
        $aiProvider = $this->getAiProvider($input);
        if ($aiProvider instanceof Error) {
            $output->writeln("<error>Error: {$aiProvider}</error>");

            return Command::FAILURE;
        }

        $get = $this->getBooleanOption($input, 'get');
        if ($get instanceof Error) {
            $output->writeln("<error>Error: {$get}</error>");

            return Command::FAILURE;
        }

        if ($get) {
            $apiKey = $this->handleGetOption($input, $aiProvider);
            if ($apiKey instanceof Error) {
                $output->writeln("<error>Error: {$apiKey}</error>");

                return Command::FAILURE;
            }

            $output->writeln((string) $apiKey);

            return Command::SUCCESS;
        }

        $remove = $this->getBooleanOption($input, 'remove');
        if ($remove instanceof Error) {
            $output->writeln("<error>Error: {$remove}</error>");

            return Command::FAILURE;
        }

        if ($remove) {
            $apiKeyIsRemoved = $this->handleRemoveOption($input, $aiProvider);
            if ($apiKeyIsRemoved instanceof Error) {
                $output->writeln("<error>Error: {$apiKeyIsRemoved}</error>");

                return Command::FAILURE;
            }

            $output->writeln('<info>Success: API key removed successfully!</info>');

            return Command::SUCCESS;
        }

        $apiKey = $input->getArgument('api-key');
        if (! $apiKey || ! is_string($apiKey)) {
            $output->writeln('<error>Error: Invalid API key provided.</error>');

            return Command::FAILURE;
        }

        $apiKeyType = ApiKeyFactory::create($aiProvider, $apiKey);
        if ($apiKeyType instanceof Error) {
            $output->writeln("<error>Error: {$apiKeyType}</error>");

            return Command::FAILURE;
        }

        $apiKeyIsSet = $this->setApiKey->execute($aiProvider, $apiKeyType);
        if ($apiKeyIsSet instanceof Error) {
            $output->writeln("<error>Error: {$apiKeyIsSet}</error>");

            return Command::FAILURE;
        }

        $output->writeln('<info>Success: API key set successfully!</info>');

        return Command::SUCCESS;
    }

    private function getAiProvider(InputInterface $input): AiProvider|Error
    {
        $customAiProviderName = $input->getOption('provider');
        if (! is_null($customAiProviderName)) {
            if (! $customAiProviderName || ! is_string($customAiProviderName)) {
                return Error::parse('Invalid AI provider provided.');
            }

            $customAiProvider = Aiprovider::parse($customAiProviderName);
            if ($customAiProvider instanceof Error) {
                return $customAiProvider;
            }

            $customAiProviderIsEnabled = $this->checkAiProviderIsEnabled->execute($customAiProvider);
            if ($customAiProviderIsEnabled instanceof Error) {
                return $customAiProviderIsEnabled;
            }

            if (! $customAiProviderIsEnabled) {
                return Error::parse(
                    sprintf(
                        'The "%s" AI provider is not enabled.',
                        $customAiProvider->getFormattedValue()
                    )
                );
            }

            return $customAiProvider;
        }

        return $this->getDefaultAiProvider->execute();
    }

    private function handleGetOption(InputInterface $input, AiProvider $aiProvider): ApiKeyInterface|Error
    {
        $apiKeyIsProvided = $this->checkArgumentIsProvided($input, 'api-key');
        if ($apiKeyIsProvided instanceof Error) {
            return $apiKeyIsProvided;
        }

        if ($apiKeyIsProvided) {
            return Error::parse('The "--get" option cannot be used with the "api-key" argument.');
        }

        return $this->getApiKey->execute($aiProvider);
    }

    private function handleRemoveOption(InputInterface $input, AiProvider $aiProvider): true|Error
    {
        $apiKeyIsProvided = $this->checkArgumentIsProvided($input, 'api-key');
        if ($apiKeyIsProvided instanceof Error) {
            return $apiKeyIsProvided;
        }

        if ($apiKeyIsProvided) {
            return Error::parse('The "--get" option cannot be used with the "api-key" argument.');
        }

        return $this->removeApiKey->execute($aiProvider);
    }
}
