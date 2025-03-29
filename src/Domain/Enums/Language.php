<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Enums;

use YSOCode\Commit\Domain\Enums\Interfaces\EnumInterface;
use YSOCode\Commit\Domain\Enums\Traits\WithValueToolsTrait;
use YSOCode\Commit\Domain\Types\Error;

enum Language: string implements EnumInterface
{
    use WithValueToolsTrait;

    case EN_US = 'en_US';
    case PT_BR = 'pt_BR';
    case ES_ES = 'es_ES';

    public static function parse(string $value): self|Error
    {
        return match ($value) {
            self::EN_US->value => self::EN_US,
            self::PT_BR->value => self::PT_BR,
            self::ES_ES->value => self::ES_ES,
            default => Error::parse("Invalid language {$value}."),
        };
    }

    public function getFormattedValue(): string
    {
        return match ($this) {
            self::EN_US => 'English (United States)',
            self::PT_BR => 'Portuguese (Brazil)',
            self::ES_ES => 'Spanish (Spain)',
        };
    }
}
