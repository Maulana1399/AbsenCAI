<?php

namespace App\Services\Pengajian;

use App\Models\DesaAccessGrant;
use App\Models\EventAttendance;
use App\Models\Participation;
use App\Models\Person;

class PengajianDesaReportService
{
    public function summary(DesaAccessGrant $grant): array
    {
        $eventId = $grant->event_id;
        $desaId = $grant->desa_id;

        $totalWarga = Person::query()
            ->where('desa_id', $desaId)
            ->count();

        $sudahHadir = Person::query()
            ->where('people.desa_id', $desaId)
            ->whereExists(function ($q) use ($eventId) {
                $q->selectRaw('1')
                    ->from('participations')
                    ->join('event_attendances', 'event_attendances.participation_id', '=', 'participations.id')
                    ->whereColumn('participations.person_id', 'people.id')
                    ->where('participations.event_id', $eventId);
            })
            ->count();

        $selfCount = EventAttendance::query()
            ->where('event_attendances.method', 'self')
            ->where('event_attendances.desa_id', $desaId)
            ->whereExists(function ($q) use ($eventId) {
                $q->selectRaw('1')
                    ->from('participations')
                    ->whereColumn('participations.id', 'event_attendances.participation_id')
                    ->where('participations.event_id', $eventId);
            })
            ->count();

        $operatorCount = EventAttendance::query()
            ->where('event_attendances.method', 'operator')
            ->where('event_attendances.desa_id', $desaId)
            ->whereExists(function ($q) use ($eventId) {
                $q->selectRaw('1')
                    ->from('participations')
                    ->whereColumn('participations.id', 'event_attendances.participation_id')
                    ->where('participations.event_id', $eventId);
            })
            ->count();

        return [
            'total_warga' => $totalWarga,
            'sudah_hadir' => $sudahHadir,
            'belum_hadir' => $totalWarga - $sudahHadir,
            'self' => $selfCount,
            'operator' => $operatorCount,
        ];
    }

    public function attendanceList(
        DesaAccessGrant $grant,
        ?string $search = null,
        ?string $status = null,
        ?string $method = null,
    ): array {
        $eventId = $grant->event_id;
        $desaId = $grant->desa_id;

        $query = Person::query()
            ->where('people.desa_id', $desaId);

        if ($search !== null && mb_strlen(trim($search)) >= 3) {
            $query->where('people.nama', 'like', '%'.trim($search).'%');
        }

        $query->orderBy('people.nama');

        $persons = $query->get(['people.id', 'people.nama']);

        $result = [];

        foreach ($persons as $person) {
            $participation = Participation::query()
                ->where('person_id', $person->id)
                ->where('event_id', $eventId)
                ->first();

            $attendance = null;
            if ($participation) {
                $attendance = EventAttendance::query()
                    ->where('participation_id', $participation->id)
                    ->first();
            }

            $hadir = $attendance !== null;
            $itemMethod = $hadir ? $attendance->method : null;
            $attendedAt = $hadir ? $attendance->attended_at->format('d M Y H:i') : null;

            if ($status === 'hadir' && ! $hadir) {
                continue;
            }

            if ($status === 'belum' && $hadir) {
                continue;
            }

            if ($method !== null && $method !== '' && $itemMethod !== $method) {
                continue;
            }

            $result[] = [
                'id' => $person->id,
                'nama' => $person->nama,
                'hadir' => $hadir,
                'attended_at' => $attendedAt,
                'method' => $itemMethod,
            ];
        }

        return $result;
    }
}
