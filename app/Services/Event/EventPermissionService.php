<?php

namespace App\Services\Event;

use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\User;
use App\Support\ActiveEventContext;

class EventPermissionService
{
    public function allows(User $user, string $permission, ?Event $event = null): bool
    {
        $event = $event ?? app(ActiveEventContext::class)->current();

        if ($event === null || $user->person_id === null) {
            return false;
        }

        return EventCommitteeAssignment::query()
            ->where('event_id', $event->id)
            ->where('person_id', $user->person_id)
            ->whereHas('eventRole', function ($query) use ($permission) {
                $query->where('is_active', true)
                    ->whereJsonContains('permissions', $permission);
            })
            ->exists();
    }

    public function permissionsFor(User $user, ?Event $event = null): array
    {
        $event = $event ?? app(ActiveEventContext::class)->current();

        if ($event === null || $user->person_id === null) {
            return [];
        }

        return EventCommitteeAssignment::query()
            ->where('event_id', $event->id)
            ->where('person_id', $user->person_id)
            ->whereHas('eventRole', fn ($query) => $query->where('is_active', true))
            ->with('eventRole')
            ->get()
            ->flatMap(fn ($assignment) => $assignment->eventRole?->permissions ?? [])
            ->unique()
            ->values()
            ->all();
    }
}
