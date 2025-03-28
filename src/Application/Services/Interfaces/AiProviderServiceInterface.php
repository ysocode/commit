<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Services\Interfaces;

use Closure;
use YSOCode\Commit\Domain\Types\Error;

interface AiProviderServiceInterface
{
    public function generateCommitMessage(string $prompt, string $diff, Closure $onProgress): string|Error;
}
