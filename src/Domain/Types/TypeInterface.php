<?php

namespace YSOCode\Commit\Domain\Types;

use Stringable;

interface TypeInterface extends Stringable
{
    public function value(): mixed;

    public static function parse(string $value): self;
}
