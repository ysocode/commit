<?php

namespace YSOCode\Commit\Actions;

use YSOCode\Commit\Actions\Interfaces\ActionInterface;
use YSOCode\Commit\Actions\Traits\HasObserversTrait;
use YSOCode\Commit\Domain\Enums\AI;
use YSOCode\Commit\Domain\Enums\Lang;
use YSOCode\Commit\Domain\Enums\Status;
use YSOCode\Commit\Domain\Types\Error;

class GenerateConventionalCommitMessage implements ActionInterface
{
    use HasObserversTrait;

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

        $onProgress = function () {
            $this->notifyProgress(Status::RUNNING);

            usleep(100000);
        };

        $conventionalCommit = match ($this->ai) {
            default => Error::parse('Unsupported AI provider'),
            AI::COHERE => (new GenerateCommitWithCohereAI(
                $this->systemPrompt,
                $this->ai,
                $this->gitDiff,
                $onProgress
            ))->execute(),
            // AI::OPENAI => ,
            // AI::DEEPSEEK => ,
            AI::SOURCEGRAPH => (new GenerateCommitWithSourcegraphAI(
                $this->systemPrompt,
                $this->ai,
                $this->gitDiff,
                $onProgress
            ))->execute(),
        };

        if ($conventionalCommit instanceof Error) {
            $this->notifyProgress(Status::FAILED);

            return $conventionalCommit;
        }

        $this->notifyProgress(Status::FINISHED);

        return $conventionalCommit;
    }
}
