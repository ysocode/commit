<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Interfaces;

use YSOCode\Commit\Domain\Types\Error;

interface GenerateCommitMessageInterface
{
    public function __construct(string $apiKey);

    public function execute(string $prompt, string $diff): string|Error;
}
