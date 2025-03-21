<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Commands\Interfaces;

use Closure;
use YSOCode\Commit\Domain\Enums\Status;
use YSOCode\Commit\Domain\Types\Error;

interface GenerateCommitMessageInterface
{
    public function __construct(string $apiKey);

    public function subscribe(Closure $observer): void;

    public function notify(Status $status): void;

    public function execute(string $prompt, string $diff): string|Error;
}
