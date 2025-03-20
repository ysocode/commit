<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions;

use YSOCode\Commit\Domain\Enums\Status;

trait WithObserverToolsTrait
{
    /** @var array<callable> */
    private array $observers = [];

    public function subscribe(callable $callback): void
    {
        $this->observers[] = $callback;
    }

    public function notify(Status $status): void
    {
        foreach ($this->observers as $callback) {
            $callback($status);
        }
    }
}
