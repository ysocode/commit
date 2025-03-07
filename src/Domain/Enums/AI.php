<?php

namespace YSOCode\Commit\Domain\Enums;

enum AI: string
{
    const array FORMATTED_AI_NAMES = [
        'cohere' => 'Cohere',
        'openai' => 'OpenAI',
        'deepseek' => 'DeepSeek',
        'sourcegraph' => 'Sourcegraph',
    ];

    case COHERE = 'cohere';
    case OPENAI = 'openai';
    case DEEPSEEK = 'deepseek';
    case SOURCEGRAPH = 'sourcegraph';

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
