<?php

namespace App\Services\QR;

use App\Models\Event;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;

class QRIdentityResolver
{
    public function resolve(string $identifier, ?Event $event = null): ?Participation
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        $participation = Participation::with('person', 'event')
            ->whereRaw('LOWER(attendance_code) = ?', [strtolower($identifier)])
            ->first();

        if ($participation !== null) {
            return $this->matchesEvent($participation, $event) ? $participation : null;
        }

        $mapping = LegacyPesertaMapping::with(['participation.person', 'participation.event', 'person'])
            ->whereRaw('LOWER(legacy_attendance_code) = ?', [strtolower($identifier)])
            ->first();

        if ($mapping === null || $mapping->participation === null) {
            return null;
        }

        if ((int) $mapping->event_id !== (int) $mapping->participation->event_id) {
            return null;
        }

        if ($event !== null && (int) $mapping->participation->event_id !== (int) $event->id) {
            return null;
        }

        if ($mapping->person_id !== null && (int) $mapping->person_id !== (int) $mapping->participation->person_id) {
            return null;
        }

        return $mapping->participation;
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
