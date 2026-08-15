<?php

namespace App\Services\Event;

use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class EventAccessService
{
    /**
     * Whether a user may access an event.
     *
     * Resolution order:
     * 1. Super Admin / Admin (platform roles) → existing global rule.
     * 2. User-based event membership (event_committee_assignments.user_id).
     * 3. Person-based event membership (event_committee_assignments.person_id,
     *    via the user's linked Person).
     */
    public function canAccess(User $user, Event $event): bool
    {
        if ($user->isPlatformUser()) {
            return true;
        }

        return $this->isUserAssignedToEvent($user, $event->id);
    }

    public function isUserAssignedToEvent(User $user, int $eventId): bool
    {
        return $this->membershipQuery($user)
            ->where('event_id', $eventId)
            ->exists();
    }

    /**
     * @return array<int, int>
     */
    public function getAssignedEventIds(User $user): array
    {
        return $this->membershipQuery($user)
            ->pluck('event_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Query for every membership that grants the user access: assignments bound
     * directly to the user (user_id) OR to the user's linked Person (person_id).
     */
    private function membershipQuery(User $user): Builder
    {
        return EventCommitteeAssignment::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('user_id', $user->id);

                if ($user->person_id !== null) {
                    $query->orWhere('person_id', $user->person_id);
                }
            });
    }
}
