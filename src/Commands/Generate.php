<?php

namespace YSOCode\Commit\Commands;

use Dotenv\Dotenv;
use Illuminate\Http\Client\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'generate',
    description: 'Generate a conventional Git commit message using AI based on a Git diff.'
)]
class Generate extends Command
{
    protected function configure(): void
    {
        $helperMessage = <<<'HELP'
        This command generates a conventional commit message by analyzing the provided Git diff
        and using AI to create a message that adheres to the conventional commit standards.
        HELP;

        $this->setHelp($helperMessage)
            ->addOption('ai', 'a', InputOption::VALUE_OPTIONAL, 'Decide which AI model to use');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $homeDir = env('HOME');

        if (! $homeDir) {
            $output->writeln("<error>Error: Unable to determine the user's home directory</error>");
        }

        $configDir = "{$homeDir}/.ysocode/commit";
        $configFile = "{$configDir}/.env";

        if (! file_exists($configFile)) {
            $output->writeln("<error>Error: Configuration file .env not found at {$configFile}</error>");
        }

        $dotenv = Dotenv::createImmutable($configDir);
        $dotenv->load();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $process = new Process(['git', 'diff', '--staged']);
        $process->run();

        if (! $process->isSuccessful()) {
            $output->writeln('<error>Error: Unable to retrieve the Git diff</error>');

            return Command::FAILURE;
        }

        $gitDiff = $process->getOutput();

        if (! $gitDiff) {
            $output->writeln('<error>Error: No changes found in the Git diff</error>');

            return Command::FAILURE;
        }

        $ai = $input->getOption('ai') ?? 'cohere';

        $allowedAIList = ['cohere', 'openai'];

        if (! in_array($ai, $allowedAIList)) {
            $allowedAIListAsString = $this->formatArrayToString($allowedAIList);

            $output->writeln("<error>Error: Invalid AI model. Use one of the following: $allowedAIListAsString</error>");

            return Command::FAILURE;
        }

        $endPointList = [
            'cohere' => 'https://api.cohere.com/v2/chat',
            'openai' => 'https://api.openai.com/v1/chat/completions',
        ];

        $tokenVariableList = [
            'cohere' => 'COHERE_KEY',
            'openai' => 'OPENAI_KEY',
        ];

        $endPoint = $endPointList[$ai];
        $tokenVariable = $tokenVariableList[$ai];

        $tokenVariableValue = env($tokenVariable);
        if (! $tokenVariableValue) {
            $output->writeln("<error>Error: API key for $ai not found in the configuration file</error>");

            return Command::FAILURE;
        }

        $http = new Factory;
        $response = $http->accept('application/json')
            ->withToken(env($tokenVariable))
            ->post($endPoint, [
                'model' => 'command-r-plus-08-2024',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a Git commit message generator. Generate a conventional commit message based on the provided git diff.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $gitDiff,
                    ],
                ],
                'temperature' => 0.7,
            ]);

        $responseDecoded = json_decode($response->getBody(), true);

        [$content] = $responseDecoded['message']['content'];

        $commitMessage = $content['text'];

        $output->writeln('<info>Generated Commit Message:</info>');
        $output->writeln([
            $commitMessage,
            '',
        ]);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>Do you want to create a commit with this message? [y/N]</question>', false);

        if (! $helper->ask($input, $output, $question)) {
            $output->writeln('<info>No commit made</info>');

            return Command::SUCCESS;
        }

        $commitProcess = new Process(['git', 'commit', '-m', $commitMessage]);
        $commitProcess->run();

        if (! $commitProcess->isSuccessful()) {
            $output->writeln('<error>Error: Unable to create commit</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>Commit created successfully!</info>');

        return Command::SUCCESS;
    }

    private function formatArrayToString(array $allowedAIs): string
    {
        $count = count($allowedAIs);

        if ($count > 1) {
            $lastItem = array_pop($allowedAIs);

            return $count > 2
                ? implode(', ', $allowedAIs).' e '.$lastItem
                : implode(' ou ', [$allowedAIs[0], $lastItem]);
        }

        return $allowedAIs[0];
    }
}
