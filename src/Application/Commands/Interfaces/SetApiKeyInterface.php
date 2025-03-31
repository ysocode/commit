<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Commands\Interfaces;

use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Domain\Types\Interfaces\ApiKeyInterface;

interface SetApiKeyInterface
{
    public function execute(AiProvider $aiProvider, ApiKeyInterface $apiKey): true|Error;
}
