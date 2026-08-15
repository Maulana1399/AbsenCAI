<?php

namespace App\Services\Event;

use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\User;
use App\Support\ActiveEventContext;
use Illuminate\Database\Eloquent\Builder;

class EventPermissionService
{
    /**
     * Whether a user holds a permission inside an event.
     *
     * Membership is resolved through the user's own assignments (user_id) OR
     * the assignments of the user's linked Person (person_id). Platform roles
     * (Super Admin / Admin) are handled by the Gates in AppServiceProvider —
     * this service only evaluates EventRole permissions.
     */
    public function allows(User $user, string $permission, ?Event $event = null): bool
    {
        $event = $event ?? app(ActiveEventContext::class)->current();

        if ($event === null) {
            return false;
        }

        return $this->membershipQuery($user, $event->id)
            ->whereHas('eventRole', function (Builder $query) use ($permission) {
                $query->where('is_active', true)
                    ->whereJsonContains('permissions', $permission);
            })
            ->exists();
    }

    /**
     * @return array<int, string>
     */
    public function permissionsFor(User $user, ?Event $event = null): array
    {
        $event = $event ?? app(ActiveEventContext::class)->current();

        if ($event === null) {
            return [];
        }

        return $this->membershipQuery($user, $event->id)
            ->whereHas('eventRole', fn (Builder $query) => $query->where('is_active', true))
            ->with('eventRole')
            ->get()
            ->flatMap(fn ($assignment) => $assignment->eventRole?->permissions ?? [])
            ->unique()
            ->values()
            ->all();
    }

    private function membershipQuery(User $user, int $eventId): Builder
    {
        return EventCommitteeAssignment::query()
            ->where('event_id', $eventId)
            ->where(function (Builder $query) use ($user) {
                $query->where('user_id', $user->id);

                if ($user->person_id !== null) {
                    $query->orWhere('person_id', $user->person_id);
                }
            });
    }
}
