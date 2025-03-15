<?php

namespace YSOCode\Commit\Domain\Types;

use DomainException;

final readonly class Error implements TypeInterface
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
        if (! $value) {
            throw new DomainException('Error cannot be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
