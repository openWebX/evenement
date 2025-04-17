<?php declare(strict_types=1);

namespace Evenement;

use InvalidArgumentException;

trait EventEmitterTrait
{
    /**
     * Holds all listeners for each event.
     *
     * @var array<string, array<int, array{0:callable, 1:bool}>>  // [ [listener, onceFlag], … ]
     */
    private array $listeners = [];

    /**
     * Attach a listener that will be called every time $event is emitted.
     */
    public function on(string $event, callable $listener): static
    {
        $this->assertValidEvent($event);
        $this->listeners[$event][] = [$listener, false];
        return $this;
    }

    /**
     * Attach a listener that will be called only the first time $event is emitted.
     */
    public function once(string $event, callable $listener): static
    {
        $this->assertValidEvent($event);
        $this->listeners[$event][] = [$listener, true];
        return $this;
    }

    /**
     * Remove a specific listener (either once- or persistent) for $event.
     */
    public function removeListener(string $event, callable $listener): void
    {
        $this->assertValidEvent($event);

        if (empty($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $key => [$cb, $once]) {
            if ($cb === $listener) {
                unset($this->listeners[$event][$key]);
            }
        }

        if (empty($this->listeners[$event])) {
            unset($this->listeners[$event]);
        } else {
            // reindex
            $this->listeners[$event] = array_values($this->listeners[$event]);
        }
    }

    /**
     * Remove all listeners, or only those for a specific $event.
     */
    public function removeAllListeners(?string $event = null): void
    {
        if ($event !== null) {
            unset($this->listeners[$event]);
        } else {
            $this->listeners = [];
        }
    }

    /**
     * Get all listeners (once and persistent), either for $event or for every event.
     *
     * @return array<string, list<callable>>|list<callable>
     */
    public function listeners(?string $event = null): array
    {
        if ($event !== null) {
            return array_column($this->listeners[$event] ?? [], 0);
        }

        return array_map(function ($entries) {
            return array_column($entries, 0);
        }, $this->listeners);
    }

    /**
     * Emit $event, passing all following args to each listener.
     */
    public function emit(string $event, mixed ...$arguments): void
    {
        $this->assertValidEvent($event);

        if (empty($this->listeners[$event])) {
            return;
        }

        // We need to reindex here, because we may unset items mid‑loop
        $entries = $this->listeners[$event];
        foreach ($entries as $idx => [$listener, $once]) {
            $listener(...$arguments);

            if ($once) {
                unset($this->listeners[$event][$idx]);
            }
        }

        if (empty($this->listeners[$event])) {
            unset($this->listeners[$event]);
        } else {
            // ensure our keys stay sequential
            $this->listeners[$event] = array_values($this->listeners[$event]);
        }
    }

    protected function assertValidEvent(string $event): void
    {
        if ($event === '') {
            throw new InvalidArgumentException('Event name must not be an empty string.');
        }
    }
}
