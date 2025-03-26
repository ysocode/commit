<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Services\Types;

use DomainException;
use YSOCode\Commit\Application\Services\Types\Interfaces\ApiKeyInterface;

readonly class SoucegraphApiKey implements ApiKeyInterface
{
    public function __construct(private string $value) {}

    public function getValue(): string
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
        if (in_array(preg_match('/^sgp_[a-f0-9]{16}_[a-f0-9]{40}$/', $value), [0, false], true)) {
            throw new DomainException('Invalid Sourcegraph API key format.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
