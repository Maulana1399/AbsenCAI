<?php

namespace App\Services\Pengajian;

use App\Models\DesaAccessGrant;
use App\Models\EventAttendance;
use App\Models\Person;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
                    ->where('participations.event_id', $eventId)
                    ->where('event_attendances.status', EventAttendance::STATUS_HADIR);
            })
            ->count();

        $izinCount = Person::query()
            ->where('people.desa_id', $desaId)
            ->whereExists(function ($q) use ($eventId) {
                $q->selectRaw('1')
                    ->from('participations')
                    ->join('event_attendances', 'event_attendances.participation_id', '=', 'participations.id')
                    ->whereColumn('participations.person_id', 'people.id')
                    ->where('participations.event_id', $eventId)
                    ->where('event_attendances.status', EventAttendance::STATUS_IZIN);
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
            'izin' => $izinCount,
            'belum_hadir' => $totalWarga - $sudahHadir - $izinCount,
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
            ->select([
                'people.id',
                'people.nama',
                'participations.participant_number',
                'event_attendances.id as attendance_id',
                'event_attendances.status as attendance_status',
                'event_attendances.method as attendance_method',
                'event_attendances.attended_at',
            ])
            ->leftJoin('participations', function ($join) use ($eventId) {
                $join->on('participations.person_id', '=', 'people.id')
                    ->where('participations.event_id', '=', $eventId);
            })
            ->leftJoin('event_attendances', 'event_attendances.participation_id', '=', 'participations.id')
            ->where('people.desa_id', $desaId);

        if ($search !== null && mb_strlen(trim($search)) >= 3) {
            $trimmed = trim($search);
            $query->where(function ($q) use ($trimmed) {
                $q->where('people.nama', 'like', '%'.$trimmed.'%')
                    ->orWhere('participations.participant_number', 'like', '%'.$trimmed.'%');
            });
        }

        if ($status === 'hadir') {
            $query->where('event_attendances.status', EventAttendance::STATUS_HADIR);

            if ($method !== null && $method !== '') {
                $query->where('event_attendances.method', $method);
            }
        } elseif ($status === 'izin') {
            $query->where('event_attendances.status', EventAttendance::STATUS_IZIN);
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
            $status = $row->attendance_status;

            $hadir = $status === EventAttendance::STATUS_HADIR;
            $izin = $status === EventAttendance::STATUS_IZIN;

            return [
                'id' => $row->id,
                'nama' => $row->nama,
                'participant_number' => $row->participant_number,
                'hadir' => $hadir,
                'izin' => $izin,
                'attended_at' => ($hadir || $izin) && $row->attended_at ? Carbon::parse($row->attended_at)->format('d M Y H:i') : null,
                'method' => ($hadir || $izin) ? $row->attendance_method : null,
            ];
        })->all();
    }
}
