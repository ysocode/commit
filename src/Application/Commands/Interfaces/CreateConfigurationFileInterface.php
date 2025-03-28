<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Commands\Interfaces;

use YSOCode\Commit\Domain\Types\Error;

interface CreateConfigurationFileInterface
{
    public function execute(bool $force = false): true|Error;
}
