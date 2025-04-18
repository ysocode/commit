<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Types\Interfaces;

use Stringable;
use YSOCode\Commit\Domain\Types\Error;

interface ApiKeyInterface extends Stringable
{
    public function __construct(string $value);

    public function getValue(): string;

    public static function parse(string $value): self|Error;
}
