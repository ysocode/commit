<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Types;

use DomainException;
use Stringable;

readonly class Error implements Stringable
{
    public function __construct(private string $value) {}

    public function value(): string
    {
        return $this->value;
    }

    public static function parse(string $value): self
    {
        self::validate($value);

        return new self($value);
    }

    private static function validate(string $value): void
    {
        if ($value === '' || $value === '0') {
            throw new DomainException('Error cannot be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
