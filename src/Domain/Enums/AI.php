<?php

namespace YSOCode\Commit\Domain\Enums;

enum AI: string
{
    const array FORMATTED_AI_NAMES = [
        'cohere' => 'Cohere',
        'openai' => 'OpenAI',
    ];

    case COHERE = 'cohere';
    case OPENAI = 'openai';

    public function formattedValue(): string
    {
        return self::FORMATTED_AI_NAMES[$this->value];
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn ($enum) => $enum->value, self::cases());
    }
}
