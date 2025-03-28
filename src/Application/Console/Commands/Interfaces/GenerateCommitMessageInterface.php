<?php

namespace YSOCode\Commit\Application\Console\Commands\Interfaces;

use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Interfaces\ObservableInterface;
use YSOCode\Commit\Domain\Types\Error;

interface GenerateCommitMessageInterface extends ObservableInterface
{
    public function execute(AiProvider $aiProvider, string $prompt, string $diff): string|Error;
}
