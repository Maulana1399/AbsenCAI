<?php

namespace App\Services\Event;

use App\Enums\Role;
use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\User;

class EventAccessService
{
    public function canAccess(User $user, Event $event): bool
    {
        if ($user->role !== Role::KetuaEvent) {
            return true;
        }

        return $this->isUserAssignedToEvent($user, $event->id);
    }

    public function isUserAssignedToEvent(User $user, int $eventId): bool
    {
        if ($user->person_id === null) {
            return false;
        }

        return EventCommitteeAssignment::where('person_id', $user->person_id)
            ->where('event_id', $eventId)
            ->exists();
    }

    public function getAssignedEventIds(User $user): array
    {
        if ($user->person_id === null) {
            return [];
        }

        return EventCommitteeAssignment::where('person_id', $user->person_id)
            ->pluck('event_id')
            ->toArray();
    }
}
