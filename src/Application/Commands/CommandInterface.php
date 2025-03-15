<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Commands;

interface CommandInterface
{
    public function getName(): string;
}
