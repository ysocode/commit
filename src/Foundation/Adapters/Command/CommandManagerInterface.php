<?php

namespace YSOCode\Commit\Foundation\Adapters\Command;

interface CommandManagerInterface
{
    public function registerCommand(CommandInterface $command): void;
}
