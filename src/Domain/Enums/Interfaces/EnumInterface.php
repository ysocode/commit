<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Enums\Interfaces;

use YSOCode\Commit\Domain\Types\Error;

interface EnumInterface
{
    public static function parse(string $value): self|Error;

    public function formattedValue(): string;

    /**
     * @return array<string|int|float>
     */
    public static function values(): array;
}
