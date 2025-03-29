<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Enums;

use YSOCode\Commit\Domain\Enums\Interfaces\EnumInterface;
use YSOCode\Commit\Domain\Enums\Traits\WithValueToolsTrait;
use YSOCode\Commit\Domain\Types\Error;

enum AiProvider: string implements EnumInterface
{
    use WithValueToolsTrait;

    case COHERE = 'cohere';
    case OPENAI = 'openai';
    case DEEPSEEK = 'deepseek';
    case SOURCEGRAPH = 'sourcegraph';

    public static function parse(string $value): self|Error
    {
        return match ($value) {
            self::COHERE->value => self::COHERE,
            self::OPENAI->value => self::OPENAI,
            self::DEEPSEEK->value => self::DEEPSEEK,
            self::SOURCEGRAPH->value => self::SOURCEGRAPH,
            default => Error::parse("Invalid AI provider {$value}."),
        };
    }

    public function getFormattedValue(): string
    {
        return match ($this) {
            self::COHERE => 'Cohere',
            self::OPENAI => 'OpenAI',
            self::DEEPSEEK => 'DeepSeek',
            self::SOURCEGRAPH => 'Sourcegraph',
        };
    }
}
