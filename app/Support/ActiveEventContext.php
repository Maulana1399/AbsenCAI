<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Facades\Session;

class ActiveEventContext
{
    private const SESSION_KEY = 'active_event_id';

    private bool $cleared = false;

    public function current(): ?Event
    {
        $sessionId = Session::get(self::SESSION_KEY);

        if ($sessionId !== null) {
            $event = Event::active()->find($sessionId);

            if ($event !== null) {
                return $event;
            }

            Session::forget(self::SESSION_KEY);
        }

        if ($this->cleared) {
            return null;
        }

        return Event::active()->orderBy('id')->first();
    }

    public function id(): ?int
    {
        return $this->current()?->id;
    }

    public function requireCurrent(): Event
    {
        $event = $this->current();

        if ($event === null) {
            throw new \RuntimeException('No active event available.');
        }

        return $event;
    }

    public function resolveDefault(): ?Event
    {
        return Event::active()->orderBy('id')->first();
    }

    public function set(Event $event): void
    {
        if (! $event->isActive()) {
            return;
        }

        Session::put(self::SESSION_KEY, $event->id);
        $this->cleared = false;
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
        $this->cleared = true;
    }

    public function hasActiveEvent(): bool
    {
        return $this->current() !== null;
    }
}
