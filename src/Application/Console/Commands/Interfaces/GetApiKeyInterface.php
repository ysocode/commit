<?php

namespace YSOCode\Commit\Application\Console\Commands\Interfaces;

use YSOCode\Commit\Domain\Enums\AiProvider;
use YSOCode\Commit\Domain\Types\Error;
use YSOCode\Commit\Domain\Types\Interfaces\ApiKeyInterface;

interface GetApiKeyInterface
{
    public function execute(AiProvider $aiProvider): ApiKeyInterface|Error;
}
