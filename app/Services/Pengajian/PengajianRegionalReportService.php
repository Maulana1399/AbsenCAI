<?php

namespace App\Services\Pengajian;

use App\Models\Event;
use App\Models\Person;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PengajianRegionalReportService
{
    public function summary(Event $event): array
    {
        $eventId = $event->id;

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

        $attendanceCounts = DB::table('event_attendances')
            ->join('participations', 'participations.id', '=', 'event_attendances.participation_id')
            ->where('participations.event_id', $eventId)
            ->selectRaw("method, COUNT(*) as cnt")
            ->groupBy('method')
            ->pluck('cnt', 'method');

        $totalDesa = DB::table('desas')
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('people')
                    ->whereColumn('people.desa_id', 'desas.id');
            })
            ->count();

        $desaHadir = Person::query()
            ->whereNotNull('people.desa_id')
            ->whereExists(function ($q) use ($eventId) {
                $q->selectRaw('1')
                    ->from('participations')
                    ->join('event_attendances', 'event_attendances.participation_id', '=', 'participations.id')
                    ->whereColumn('participations.person_id', 'people.id')
                    ->where('participations.event_id', $eventId);
            })
            ->distinct()
            ->count('people.desa_id');

        return [
            'total_warga' => $totalWarga,
            'sudah_hadir' => $sudahHadir,
            'belum_hadir' => $totalWarga - $sudahHadir,
            'self' => $attendanceCounts->get('self', 0),
            'operator' => $attendanceCounts->get('operator', 0),
            'total_desa' => $totalDesa,
            'desa_hadir' => $desaHadir,
        ];
    }

    public function desaBreakdown(Event $event): array
    {
        $eventId = $event->id;

        $rows = DB::table('desas')
            ->leftJoin('people', 'people.desa_id', '=', 'desas.id')
            ->leftJoin('participations', function ($join) use ($eventId) {
                $join->on('participations.person_id', '=', 'people.id')
                    ->where('participations.event_id', '=', $eventId);
            })
            ->leftJoin('event_attendances', 'event_attendances.participation_id', '=', 'participations.id')
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('people as p2')
                    ->whereColumn('p2.desa_id', 'desas.id');
            })
            ->select([
                'desas.id as desa_id',
                'desas.desa_asal as desa_name',
                DB::raw('COUNT(DISTINCT people.id) as total_warga'),
                DB::raw('COUNT(DISTINCT event_attendances.id) as sudah_hadir'),
            ])
            ->groupBy('desas.id', 'desas.desa_asal')
            ->orderBy('desas.desa_asal')
            ->get();

        return $rows->map(function ($row) {
            return [
                'desa_id' => (int) $row->desa_id,
                'desa_name' => $row->desa_name,
                'total_warga' => (int) $row->total_warga,
                'sudah_hadir' => (int) $row->sudah_hadir,
                'belum_hadir' => (int) $row->total_warga - (int) $row->sudah_hadir,
            ];
        })->all();
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
            ->select([
                'people.id',
                'people.nama',
                'people.desa_id',
                'participations.participant_number',
                'event_attendances.id as attendance_id',
                'event_attendances.method as attendance_method',
                'event_attendances.attended_at',
            ])
            ->leftJoin('participations', function ($join) use ($eventId) {
                $join->on('participations.person_id', '=', 'people.id')
                    ->where('participations.event_id', '=', $eventId);
            })
            ->leftJoin('event_attendances', 'event_attendances.participation_id', '=', 'participations.id')
            ->whereNotNull('people.desa_id');

        if ($desaId !== null) {
            $query->where('people.desa_id', $desaId);
        }

        if ($search !== null && mb_strlen(trim($search)) >= 3) {
            $trimmed = trim($search);
            $query->where(function ($q) use ($trimmed) {
                $q->where('people.nama', 'like', '%'.$trimmed.'%')
                    ->orWhere('participations.participant_number', 'like', '%'.$trimmed.'%');
            });
        }

        if ($status === 'hadir') {
            $query->whereNotNull('event_attendances.id');

            if ($method !== null && $method !== '') {
                $query->where('event_attendances.method', $method);
            }
        } elseif ($status === 'belum') {
            $query->whereNull('event_attendances.id');
        } else {
            if ($method !== null && $method !== '') {
                $query->whereNotNull('event_attendances.id')
                    ->where('event_attendances.method', $method);
            }
        }

        $rows = $query->orderBy('people.nama')->get();

        return $rows->map(function ($row) {
            $hadir = $row->attendance_id !== null;

            return [
                'id' => $row->id,
                'nama' => $row->nama,
                'participant_number' => $row->participant_number,
                'hadir' => $hadir,
                'attended_at' => $hadir && $row->attended_at ? Carbon::parse($row->attended_at)->format('d M Y H:i') : null,
                'method' => $hadir ? $row->attendance_method : null,
            ];
        })->all();
    }
}
