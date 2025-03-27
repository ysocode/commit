<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Types;

use DomainException;
use YSOCode\Commit\Domain\Types\Interfaces\ErrorInterface;

readonly class Error implements ErrorInterface
{
    public function __construct(private string $value) {}

    public function getValue(): string
    {
        return $this->value;
    }

    public static function parse(string $value): self
    {
        if (! self::isValid($value)) {
            throw new DomainException('Error cannot be empty.');
        }

        return new self($value);
    }

    public static function isValid(string $value): bool
    {
        return $value !== '' && $value !== '0';
    }

    public function __toString(): string
    {
        return $this->getValue();
    }
}
