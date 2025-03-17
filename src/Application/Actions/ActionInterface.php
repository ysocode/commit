<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

interface ActionInterface
{
    public function execute(): mixed;
}
