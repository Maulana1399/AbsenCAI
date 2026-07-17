<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Facades\Session;

class ActiveEventContext
{
    private const SESSION_KEY = 'active_event_id';

    private ?Event $cached = null;

    public function current(): ?Event
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $id = $this->id();

        if ($id === null) {
            return null;
        }

        $event = Event::active()->find($id);

        if ($event === null) {
            $this->clear();

            return null;
        }

        $this->cached = $event;

        return $event;
    }

    public function id(): ?int
    {
        return Session::get(self::SESSION_KEY);
    }

    public function set(Event $event): void
    {
        if (! $event->isActive()) {
            return;
        }

        Session::put(self::SESSION_KEY, $event->id);
        $this->cached = $event;
    }

    public function switchTo(int $eventId): ?Event
    {
        $event = Event::active()->find($eventId);

        if ($event === null) {
            return null;
        }

        $this->set($event);

        return $event;
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
        $this->cached = null;
    }

    public function hasActiveEvent(): bool
    {
        return $this->id() !== null && $this->current() !== null;
    }
}
