<?php

namespace YSOCode\Commit\Actions;

interface Action
{
    public function execute(): mixed;
}
