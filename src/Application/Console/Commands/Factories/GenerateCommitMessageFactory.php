<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Commands\Factories;

use Exception;
use YSOCode\Commit\Application\Actions\GenerateCommitMessageWithSourcegraph;
use YSOCode\Commit\Application\Console\Commands\Abstracts\GenerateCommitMessageAbstract;
use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Foundation\Support\UserConfiguration;

readonly class GenerateCommitMessageFactory
{
    public function __construct(private UserConfiguration $userConfiguration) {}

    /**
     * @throws Exception
     */
    public function create(
        AiProvider $aiProvider,
        string $prompt,
        string $diff
    ): GenerateCommitMessageAbstract|Error {
        return match ($aiProvider) {
            AiProvider::SOURCEGRAPH => new GenerateCommitMessageWithSourcegraph(
                $this->userConfiguration,
                $prompt,
                $diff
            ),
            default => Error::parse('AI provider commit message generator not found.'),
        };
    }
}
