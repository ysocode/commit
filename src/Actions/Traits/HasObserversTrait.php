<?php

namespace YSOCode\Commit\Actions\Traits;

use YSOCode\Commit\Domain\Enums\Status;

trait HasObserversTrait
{
    /**
     * @var array<callable(Status): void>
     */
    private array $subscribers = [];

    /**
     * Subscribe a callback to be notified of status updates.
     * The callback should accept a Status as parameter and return nothing.
     */
    public function subscribe(callable $callback): void
    {
        $this->subscribers[] = $callback;
    }

    /**
     * Notify all subscribers about a status update.
     * This will trigger the subscribed callbacks with the given status.
     */
    protected function notifyProgress(Status $status): void
    {
        foreach ($this->subscribers as $callback) {
            $callback($status);
        }
    }
}
