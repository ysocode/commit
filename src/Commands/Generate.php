<?php

namespace YSOCode\Commit\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(
    name: 'generate',
    description: 'Generate a conventional Git commit message using AI based on a Git diff'
)]
class Generate extends Command
{
    protected function configure(): void
    {
        $helperMessage = <<<'HELP'
        This command generates a conventional commit message by analyzing the provided Git diff
        and using AI to create a message that adheres to the conventional commit standards
        HELP;
        $this->setHelp($helperMessage);
    }
}
