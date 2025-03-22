<?php

declare(strict_types=1);

namespace YSOCode\Commit\Application\Console\Commands\Traits;

use Closure;
use YSOCode\Commit\Domain\Enums\Status;

trait WithObserverToolsTrait
{
    /** @var array<Closure> */
    protected array $observers = [];

    public function subscribe(Closure $observer): void
    {
        $this->observers[] = $observer;
    }

    protected function notify(Status $status): void
    {
        foreach ($this->observers as $observer) {
            $observer($status);
        }
    }
}
