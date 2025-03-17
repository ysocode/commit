<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Enums;

use YSOCode\Commit\Domain\Types\Error;

enum AiProvider: string
{
    case COHERE = 'cohere';
    case OPENAI = 'openai';
    case DEEPSEEK = 'deepseek';
    case SOURCEGRAPH = 'sourcegraph';

    public static function parse(string $aiProvider): self|Error
    {
        return match ($aiProvider) {
            self::COHERE->value => self::COHERE,
            self::OPENAI->value => self::OPENAI,
            self::DEEPSEEK->value => self::DEEPSEEK,
            self::SOURCEGRAPH->value => self::SOURCEGRAPH,
            default => Error::parse("Invalid AI provider {$aiProvider}."),
        };
    }

    public function formattedValue(): string
    {
        return match ($this) {
            self::COHERE => 'Cohere',
            self::OPENAI => 'OpenAI',
            self::DEEPSEEK => 'DeepSeek',
            self::SOURCEGRAPH => 'Sourcegraph',
        };
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
