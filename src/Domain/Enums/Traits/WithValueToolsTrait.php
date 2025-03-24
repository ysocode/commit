<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Enums\Traits;

trait WithValueToolsTrait
{
    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn ($enum) => $enum->value, self::cases());
    }
}
