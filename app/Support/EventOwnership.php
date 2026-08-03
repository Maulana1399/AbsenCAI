<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Database\Eloquent\Model;

/**
 * Event-scoped ownership checks.
 *
 * Encapsulates the `(int) $resource->event_id === (int) $event->id` idiom that
 * was previously duplicated across controllers, Livewire components, and
 * services. Callers keep their own failure behavior (abort/throw/return).
 */
class EventOwnership
{
    public static function belongsToEvent(Model $resource, Event $event): bool
    {
        return (int) $resource->event_id === (int) $event->id;
    }
}
