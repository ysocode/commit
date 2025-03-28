<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Types;

use DomainException;
use YSOCode\Commit\Domain\Types\Interfaces\ApiKeyInterface;

readonly class SourcegraphApiKey implements ApiKeyInterface
{
    public function __construct(private string $value) {}

    public function getValue(): string
    {
        return $this->value;
    }

    public static function parse(string $value): self
    {
        if (! self::isValid($value)) {
            throw new DomainException('Invalid Sourcegraph API key format.');
        }

        return new self($value);
    }

    public static function isValid(string $value): bool
    {
        return preg_match('/^sgp_[a-f0-9]{16}_[a-f0-9]{40}$/', $value) === 1;
    }

    public function __toString(): string
    {
        return $this->getValue();
    }
}
