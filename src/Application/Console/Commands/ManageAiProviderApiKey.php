<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use YSOCode\Commit\Application\Console\Commands\Traits\WithCommandToolsTrait;
use YSOCode\Commit\Application\Services\Types\Interfaces\ApiKeyInterface;
use YSOCode\Commit\Domain\Types\Error;

class ManageAiProviderApiKey extends Command
{
    use WithCommandToolsTrait;

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

            $output->writeln('');

            return Command::SUCCESS;
        }

        return Command::SUCCESS;
    }

    private function handleGetOption(InputInterface $input): ApiKeyInterface|Error {}
}
