<?php

namespace App\Services\Attendance;

use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;

class LegacyParticipationResolver
{
    public function resolveByPesertaAndEvent(int $pesertaId, int $eventId): ?Participation
    {
        $mapping = LegacyParticipationMapping::with(['participation.person', 'peserta', 'event'])
            ->where('peserta_id', $pesertaId)
            ->where('event_id', $eventId)
            ->first();

        return ($mapping?->participation && (int) $mapping->participation->event_id === (int) $eventId)
            ? $mapping->participation
            : null;
    }

    public function resolveByPersonAndEvent(int $personId, int $eventId): ?Participation
    {
        $mapping = LegacyParticipationMapping::with(['participation.person', 'peserta', 'event'])
            ->where('person_id', $personId)
            ->where('event_id', $eventId)
            ->first();

        return ($mapping?->participation && (int) $mapping->participation->event_id === (int) $eventId)
            ? $mapping->participation
            : null;
    }

    public function resolveByParticipation(int $participationId, ?int $eventId = null): ?Participation
    {
        $mapping = LegacyParticipationMapping::with(['participation', 'person', 'peserta', 'event'])
            ->where('participation_id', $participationId)
            ->first();

        if ($mapping?->participation && ($eventId === null || (int) $mapping->event_id === (int) $eventId)) {
            return $mapping->participation;
        }

        return null;
    }

    public function resolveByLegacyAttendanceCode(string $identifier, int $eventId): ?Participation
    {
        $code = strtolower(trim($identifier));

        $mapping = LegacyParticipationMapping::with(['participation.person', 'peserta', 'event'])
            ->where('event_id', $eventId)
            ->whereHas('participation', fn ($q) => $q->whereRaw('LOWER(attendance_code) = ?', [$code]))
            ->first();

        if ($mapping?->participation && (int) $mapping->participation->event_id === (int) $eventId) {
            return $mapping->participation;
        }

        return null;
    }

    public function resolveByLegacyNip(string|int $nip, int $eventId): ?Participation
    {
        $pesertaId = peserta::where('nip', (string) $nip)->value('id');

        return $pesertaId ? $this->resolveByPesertaAndEvent((int) $pesertaId, $eventId) : null;
    }

    public function resolvePesertaByParticipation(int $participationId, ?int $eventId = null): ?peserta
    {
        $mapping = LegacyParticipationMapping::with(['peserta', 'participation', 'person', 'event'])
            ->where('participation_id', $participationId)
            ->first();

        if ($mapping?->participation && ($eventId === null || (int) $mapping->event_id === (int) $eventId)) {
            return $mapping->peserta;
        }

        return null;
    }

    public function resolvePersonByPesertaId(int $pesertaId): ?Person
    {
        return LegacyParticipationMapping::where('peserta_id', $pesertaId)->first()?->person
            ?? LegacyPesertaMapping::where('peserta_id', $pesertaId)->first()?->person;
    }

}
