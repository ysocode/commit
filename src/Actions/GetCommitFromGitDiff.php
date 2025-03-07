<?php

namespace YSOCode\Commit\Actions;

use Symfony\Component\Process\Process;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Enums\Lang;
use YSOCode\Commit\Domain\Enums\Status;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Support\Http;

class GetCommitFromGitDiff implements ActionInterface
{
    use HasObservers;

    private string $systemPrompt;

    public function __construct(
        readonly private AI $ai,
        readonly private Lang $lang,
        readonly private string $gitDiff
    ) {
        $this->systemPrompt = <<<PROMPT
        Write a commit message for this diff following Conventional Commits specification.
        ALWAYS wrap the entire commit message between ``` delimiters.
        Do NOT use scopes. 
        EACH line must not exceed 72 characters.
        Write the commit message in {$this->lang->formattedValue()} language without any accents.
        If there are multiple modifications, write the body using the list format.
        DO NOT add a period at the end of each list item, as in the following example:
        - Add a new feature
        - Fix a bug
        PROMPT;
    }

    public function execute(): string|Error
    {
        $this->notifyProgress(Status::STARTED);

        $commitFromGitDiff = match ($this->ai) {
            default => Error::parse('Unsupported AI provider'),
            AI::COHERE => $this->executeCohere(),
            // AI::OPENAI => $this->executeOpenAI(),
            // AI::DEEPSEEK => $this->executeDeepSeek(),
            AI::SOURCEGRAPH => $this->executeSourcegraph(),
        };

        $this->notifyProgress(Status::FINISHED);

        return $commitFromGitDiff;
    }

    private function executeCohere(): string|Error
    {
        $apiKey = $_ENV[apiKeyEnvVar($this->ai)];
        if (! $apiKey || ! is_string($apiKey)) {
            return Error::parse("No {$this->ai->formattedValue()} API key found");
        }

        $response = Http::create($apiKey)
            ->post(
                'https://api.cohere.com/v2/chat',
                [
                    'model' => 'command-r-plus-08-2024',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->gitDiff,
                        ],
                    ],
                    'temperature' => 0.7,
                    'stream' => false,
                ],
                function () {
                    $this->notifyProgress(Status::RUNNING);

                    usleep(100000);
                }
            );

        if ($response instanceof Error) {
            return $response;
        }

        $message = $response['message'] ?? null;
        if (! $message || ! is_array($message)) {
            return Error::parse('Missing or invalid "message" field in API response');
        }

        $content = $message['content'] ?? null;
        if (! $content || ! is_array($content)) {
            return Error::parse('Missing or invalid "content" field in message structure');
        }

        $contentFirstItem = $content[0] ?? null;
        if (! $contentFirstItem || ! is_array($contentFirstItem)) {
            return Error::parse('Empty content array or invalid first content item');
        }

        $commitMessage = $contentFirstItem['text'] ?? null;
        if (! $commitMessage || ! is_string($commitMessage)) {
            return Error::parse('Missing or non-string commit message text in response');
        }

        return $this->extractCommitMessage($commitMessage);
    }

    // private function executeOpenAI(): string|Error {}

    // private function executeDeepSeek(): string|Error {}

    private function executeSourcegraph(): string|Error
    {
        $apiKey = $_ENV[apiKeyEnvVar($this->ai)];
        if (! $apiKey || ! is_string($apiKey)) {
            return Error::parse("No {$this->ai->formattedValue()} API key found");
        }

        $command = ['cody', 'chat', '--stdin', '-m', $this->systemPrompt];

        $codyProcess = new Process(
            $command,
            null,
            [
                'SRC_ENDPOINT' => 'https://sourcegraph.com',
                'SRC_ACCESS_TOKEN' => $apiKey,
            ],
            $this->gitDiff
        );

        $codyProcess->start();

        while ($codyProcess->isRunning()) {
            $this->notifyProgress(Status::RUNNING);

            usleep(100000);
        }

        $codyProcess->wait();

        if (! $codyProcess->isSuccessful()) {
            $this->notifyProgress(Status::FAILED);

            return Error::parse('Unable to retrieve the commit from Git diff');
        }

        return $this->extractCommitMessage($codyProcess->getOutput());
    }

    private function extractCommitMessage(string $commitMessage): string|Error
    {
        $pattern = '/```(.*?)```/s';

        if (preg_match($pattern, $commitMessage, $matches)) {
            $commitMessage = trim($matches[1]);

            if (empty($commitMessage)) {
                return Error::parse('Extracted commit message is empty');
            }

            return $commitMessage;
        }

        return Error::parse('Unable to extract commit message');
    }
}
