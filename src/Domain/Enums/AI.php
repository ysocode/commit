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

    public function apiUrl(): string
    {
        return match ($this) {
            AI::COHERE => 'https://api.cohere.com/v2/chat',
            AI::OPENAI => 'https://api.openai.com/v1/chat/completions',
        };
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn ($enum) => $enum->value, self::cases());
    }
}
