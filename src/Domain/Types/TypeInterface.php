<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Types;

use Stringable;

interface TypeInterface extends Stringable
{
    public function value(): mixed;

    public static function parse(string $value): self;
}
