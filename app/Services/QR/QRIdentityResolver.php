<?php

namespace App\Services\QR;

use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use App\Services\Attendance\LegacyParticipationResolver;

class QRIdentityResolver
{
    public function resolve(string $identifier, ?Event $event = null): ?Participation
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        $resolver = app(LegacyParticipationResolver::class);

        $participation = Participation::with('person', 'event')
            ->whereRaw('LOWER(attendance_code) = ?', [strtolower($identifier)])
            ->when($event, fn ($q) => $q->where('event_id', $event->id))
            ->first();

        if ($participation !== null) {
            return $this->matchesEvent($participation, $event) ? $participation : null;
        }

        if ($event !== null) {
            $participation = $resolver->resolveByLegacyAttendanceCode($identifier, $event->id);

            if ($participation !== null) {
                return $participation;
            }
        }

        return null;
    }

    public function resolvePerson(string $identifier, ?Event $event = null): ?Person
    {
        return $this->resolve($identifier, $event)?->person;
    }

    private function matchesEvent(Participation $participation, ?Event $event): bool
    {
        return $event === null || (int) $participation->event_id === (int) $event->id;
    }
}
