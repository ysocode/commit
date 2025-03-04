<?php

namespace YSOCode\Commit\Domain\Enums;

enum Lang: string
{
    const array FORMATTED_LANGS = [
        'en' => 'English',
        'pt_br' => 'Portuguese (Brazil)',
    ];

    case EN = 'en';
    case PT_BR = 'pt_br';

    public function formattedValue(): string
    {
        return self::FORMATTED_LANGS[$this->value];
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn ($enum) => $enum->value, self::cases());
    }
}
