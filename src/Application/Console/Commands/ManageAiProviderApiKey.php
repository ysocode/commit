<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ManageAiProviderApiKey extends Command
{
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
            commit ai:key --provider=openai YOUR_API_KEY
            commit ai:key --get --provider=openai
            commit ai:key --remove --provider=openai
        HELP;

        $this->setName('ai:key')
            ->setDescription('Set, display, or remove an API key for an AI provider')
            ->setHelp($helperMessage)
            ->addArgument('api-key', InputArgument::OPTIONAL, 'API key to set for the provider')
            ->addOption('provider', 'p', InputOption::VALUE_REQUIRED, 'AI provider name')
            ->addOption('get', 'g', InputOption::VALUE_NONE, 'Display the stored API key')
            ->addOption('remove', 'r', InputOption::VALUE_NONE, 'Remove the stored API key');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
