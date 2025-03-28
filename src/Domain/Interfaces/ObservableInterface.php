<?php

namespace YSOCode\Commit\Domain\Interfaces;

use Closure;

interface ObservableInterface
{
    public function subscribe(Closure $observer): void;
}
