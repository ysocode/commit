<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Types\Interfaces;

use Stringable;

interface ErrorInterface extends Stringable
{
    public function __construct(string $value);

    public function getValue(): string;

    public static function parse(string $value): self;

    public static function isValid(string $value): bool;
}
