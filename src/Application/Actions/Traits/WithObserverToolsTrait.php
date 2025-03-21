<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Actions\Traits;

use Closure;
use YSOCode\Commit\Domain\Enums\Status;

trait WithObserverToolsTrait
{
    /** @var array<Closure> */
    private array $observers = [];

    public function subscribe(Closure $observer): void
    {
        $this->observers[] = $observer;
    }

    public function notify(Status $status): void
    {
        foreach ($this->observers as $observer) {
            $observer($status);
        }
    }
}
