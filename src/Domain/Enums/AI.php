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
     * @return array{url: string, model: string}
     */
    public function apiConfig(): array
    {
        return match ($this) {
            AI::COHERE => [
                'url' => 'https://api.cohere.com/v2/chat',
                'model' => 'command-r-plus-08-2024',
            ],
            AI::OPENAI => [
                'url' => 'https://api.openai.com/v1/chat/completions',
                'model' => 'gpt-4o-mini',
            ],
            AI::DEEPSEEK => [
                'url' => 'https://api.deepseek.com/chat/completions',
                'model' => 'deepseek-chat',
            ],
            AI::SOURCEGRAPH => [
                'url' => '',
                'model' => '',
            ],
        };
    }

    public function apiKeyEnvVar(): string
    {
        return strtoupper("{$this->value}_API_KEY");
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn ($enum) => $enum->value, self::cases());
    }
}
