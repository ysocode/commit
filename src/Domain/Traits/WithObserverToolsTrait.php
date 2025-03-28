<?php

declare(strict_types=1);

namespace YSOCode\Commit\Domain\Traits;

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

    private function notify(Status $status): void
    {
        foreach ($this->observers as $observer) {
            $observer($status);
        }
    }

    private function notifyRunningStatus(int $sleepTime = 100000): void
    {
        $this->notify(Status::RUNNING);

        usleep($sleepTime);
    }
}
