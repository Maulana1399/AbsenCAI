<?php

namespace App\Services\Attendance;

use App\Models\EventAttendance;
use App\Models\Participation;
use App\Models\SesiAbsensi;

class AttendanceReadService
{
    public function getSessionAttendance(int $eventId, int $sessionId, ?int $reguId = null): array
    {
        $session = SesiAbsensi::where('event_id', $eventId)->findOrFail($sessionId);

        $participationQuery = Participation::with([
            'person.desa',
            'regu',
            'person.legacyPesertaMapping.peserta.kelompok',
        ])->where('event_id', $eventId);

        if ($reguId) {
            $participationQuery->where('regu_id', $reguId);
        }

        $participations = $participationQuery->get();

        if ($participations->isEmpty()) {
            return [
                'participations' => collect(),
                'attendance' => collect(),
                'hadir_count' => 0,
                'izin_count' => 0,
                'belum_count' => 0,
                'total' => 0,
                'persentase' => 0,
            ];
        }

        $participationIds = $participations->pluck('id');

        $canonicalRecords = EventAttendance::whereIn('participation_id', $participationIds)
            ->where('sesi_absensi_id', $sessionId)
            ->get()
            ->keyBy('participation_id');

        $attendance = collect();
        $hadirCount = 0;
        $izinCount = 0;

        foreach ($participations as $participation) {
            $person = $participation->person;

            $canonical = $canonicalRecords->get($participation->id);

            $status = $this->resolveStatus($canonical);
            $source = $canonical !== null ? 'canonical' : 'none';

            $attendanceEntry = (object) [
                'participation' => $participation,
                'person' => $person,
                'legacyPeserta' => $person?->legacyPesertaMapping?->peserta,
                'status' => $status,
                'source' => $source,
                'jam_scan' => $canonical?->attended_at ?? null,
                'method' => $canonical?->method ?? null,
            ];

            $attendance->push($attendanceEntry);

            if ($status === 'hadir') {
                $hadirCount++;
            } elseif ($status === 'izin') {
                $izinCount++;
            }
        }

        $total = $participations->count();
        $belumCount = $total - $hadirCount - $izinCount;
        $persentase = $total > 0 ? round(($hadirCount / $total) * 100, 2) : 0;

        return [
            'participations' => $participations,
            'attendance' => $attendance,
            'hadir_count' => $hadirCount,
            'izin_count' => $izinCount,
            'belum_count' => $belumCount,
            'total' => $total,
            'persentase' => $persentase,
        ];
    }

    private function resolveStatus(?EventAttendance $canonical): string
    {
        if ($canonical !== null) {
            return $canonical->status === EventAttendance::STATUS_IZIN ? 'izin' : 'hadir';
        }

        return 'belum';
    }
}
