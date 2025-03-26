<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Services\Types\Interfaces;

use Stringable;

interface ApiKeyInterface extends Stringable
{
    public function __construct(string $value);

    public function getValue(): string;

    public static function parse(string $value): self;
}
