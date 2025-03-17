<?php

declare(strict_types=1);

namespace YSOCode\Commit\Actions;

interface ActionInterface
{
    public function execute(): mixed;
}
