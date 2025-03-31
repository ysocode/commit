<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Types;

use YSOCode\Commit\Domain\Types\Interfaces\ApiKeyInterface;

readonly class CohereApiKey implements ApiKeyInterface
{
    public function __construct(private string $value) {}

    public function getValue(): string
    {
        return $this->value;
    }

    public static function parse(string $value): self|Error
    {
        if (! self::isValid($value)) {
            return Error::parse('Invalid Cohere API key format.');
        }

        return new self($value);
    }

    private static function isValid(string $value): bool
    {
        return preg_match('/^[a-zA-Z0-9]{40}$/', $value) === 1;
    }

    public function __toString(): string
    {
        return $this->getValue();
    }
}
