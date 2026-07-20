<?php

namespace App\Services\Pengajian;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Participation;
use App\Models\Person;
use App\Models\desa;

class PengajianRegionalReportService
{
    public function summary(Event $event): array
    {
        $eventId = $event->id;

        $desas = desa::query()
            ->whereExists(function ($q) use ($eventId) {
                $q->selectRaw('1')
                    ->from('people')
                    ->whereColumn('people.desa_id', 'desas.id');
            })
            ->orderBy('desa_asal')
            ->get();

        $totalWarga = Person::query()
            ->whereNotNull('desa_id')
            ->count();

        $sudahHadir = Person::query()
            ->whereNotNull('people.desa_id')
            ->whereExists(function ($q) use ($eventId) {
                $q->selectRaw('1')
                    ->from('participations')
                    ->join('event_attendances', 'event_attendances.participation_id', '=', 'participations.id')
                    ->whereColumn('participations.person_id', 'people.id')
                    ->where('participations.event_id', $eventId);
            })
            ->count();

        $selfCount = EventAttendance::query()
            ->where('method', 'self')
            ->whereExists(function ($q) use ($eventId) {
                $q->selectRaw('1')
                    ->from('participations')
                    ->whereColumn('participations.id', 'event_attendances.participation_id')
                    ->where('participations.event_id', $eventId);
            })
            ->count();

        $operatorCount = EventAttendance::query()
            ->where('method', 'operator')
            ->whereExists(function ($q) use ($eventId) {
                $q->selectRaw('1')
                    ->from('participations')
                    ->whereColumn('participations.id', 'event_attendances.participation_id')
                    ->where('participations.event_id', $eventId);
            })
            ->count();

        $totalDesa = $desas->count();
        $desaHadir = Person::query()
            ->whereNotNull('people.desa_id')
            ->whereExists(function ($q) use ($eventId) {
                $q->selectRaw('1')
                    ->from('participations')
                    ->join('event_attendances', 'event_attendances.participation_id', '=', 'participations.id')
                    ->whereColumn('participations.person_id', 'people.id')
                    ->where('participations.event_id', $eventId);
            })
            ->distinct('people.desa_id')
            ->count('people.desa_id');

        return [
            'total_warga' => $totalWarga,
            'sudah_hadir' => $sudahHadir,
            'belum_hadir' => $totalWarga - $sudahHadir,
            'self' => $selfCount,
            'operator' => $operatorCount,
            'total_desa' => $totalDesa,
            'desa_hadir' => $desaHadir,
        ];
    }

    public function desaBreakdown(Event $event): array
    {
        $eventId = $event->id;

        $desas = desa::query()
            ->whereExists(function ($q) use ($eventId) {
                $q->selectRaw('1')
                    ->from('people')
                    ->whereColumn('people.desa_id', 'desas.id');
            })
            ->orderBy('desa_asal')
            ->get(['id', 'desa_asal']);

        $result = [];

        foreach ($desas as $desaRow) {
            $total = Person::query()
                ->where('desa_id', $desaRow->id)
                ->count();

            $hadir = Person::query()
                ->where('people.desa_id', $desaRow->id)
                ->whereExists(function ($q) use ($eventId) {
                    $q->selectRaw('1')
                        ->from('participations')
                        ->join('event_attendances', 'event_attendances.participation_id', '=', 'participations.id')
                        ->whereColumn('participations.person_id', 'people.id')
                        ->where('participations.event_id', $eventId);
                })
                ->count();

            $result[] = [
                'desa_id' => $desaRow->id,
                'desa_name' => $desaRow->desa_asal,
                'total_warga' => $total,
                'sudah_hadir' => $hadir,
                'belum_hadir' => $total - $hadir,
            ];
        }

        return $result;
    }

    public function attendanceList(
        Event $event,
        ?int $desaId = null,
        ?string $search = null,
        ?string $status = null,
        ?string $method = null,
    ): array {
        $eventId = $event->id;

        $query = Person::query()
            ->whereNotNull('people.desa_id');

        if ($desaId !== null) {
            $query->where('people.desa_id', $desaId);
        }

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
