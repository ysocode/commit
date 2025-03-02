<?php

namespace YSOCode\Commit\Domain\Types;

use DomainException;

final class Error
{
    private string $error;

    public function __construct(string $error)
    {
        $this->error = $error;
    }

    public function value(): string
    {
        return $this->error;
    }

    public static function parse(string $error): self
    {
        self::validate($error);

        return new self($error);
    }

    private static function validate(string $error): void
    {
        if (! $error) {
            throw new DomainException('Error cannot be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->error;
    }
}
