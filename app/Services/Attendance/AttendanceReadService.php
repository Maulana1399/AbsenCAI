<?php

namespace App\Services\Attendance;

use App\Models\Absensi;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\Participation;
use App\Models\SesiAbsensi;
use Illuminate\Database\Eloquent\Collection;

class AttendanceReadService
{
    public function getSessionAttendance(int $eventId, int $sessionId, ?int $reguId = null): array
    {
        $session = SesiAbsensi::where('event_id', $eventId)->findOrFail($sessionId);

        $participationQuery = Participation::with([
            'person.desa',
            'person.legacyPesertaMapping.peserta.regu',
            'person.legacyPesertaMapping.peserta.kelompok',
        ])->where('event_id', $eventId);

        if ($reguId) {
            $participationQuery->whereHas('person.legacyPesertaMapping.peserta', fn ($q) => $q->where('regu_id', $reguId));
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
        $participationByPersonId = $participations->keyBy('person_id');

        $canonicalRecords = EventAttendance::whereIn('participation_id', $participationIds)
            ->where('sesi_absensi_id', $sessionId)
            ->get()
            ->keyBy('participation_id');

        $legacyNips = $participations->map(function (Participation $p) {
            return $p->person?->legacyPesertaMapping?->peserta?->nip ?? $p->person?->nip;
        })->filter()->values();

        $legacyPesertaIds = $participations->map(function (Participation $p) {
            return $p->person?->legacyPesertaMapping?->peserta?->id;
        })->filter()->values();

        $legacyHadirByNip = Absensi::where('sesi_id', $sessionId)
            ->whereIn('nip', $legacyNips)
            ->get()
            ->keyBy('nip');

        $legacyIzinByPesertaId = IzinAbsensi::where('sesi_id', $sessionId)
            ->whereIn('peserta_id', $legacyPesertaIds)
            ->get()
            ->keyBy('peserta_id');

        $attendance = collect();
        $hadirCount = 0;
        $izinCount = 0;

        foreach ($participations as $participation) {
            $person = $participation->person;
            $legacyPeserta = $person?->legacyPesertaMapping?->peserta;
            $nip = $legacyPeserta?->nip ?? $person?->nip;
            $pesertaId = $legacyPeserta?->id;

            $canonical = $canonicalRecords->get($participation->id);
            $legacyHadir = $nip !== null ? $legacyHadirByNip->get($nip) : null;
            $legacyIzin = $pesertaId !== null ? $legacyIzinByPesertaId->get($pesertaId) : null;

            $status = $this->resolveStatus($canonical, $legacyHadir, $legacyIzin);
            $source = $canonical !== null ? 'canonical' : ($legacyHadir !== null || $legacyIzin !== null ? 'legacy' : 'none');

            $attendanceEntry = (object) [
                'participation' => $participation,
                'person' => $person,
                'legacyPeserta' => $legacyPeserta,
                'status' => $status,
                'source' => $source,
                'jam_scan' => $canonical?->attended_at ?? $legacyHadir?->jam_scan ?? null,
                'method' => $canonical?->method ?? ($legacyHadir !== null ? 'scan' : ($legacyIzin !== null ? 'izin' : null)),
            ];

            $attendance->push($attendanceEntry);

            if ($status === 'hadir') $hadirCount++;
            elseif ($status === 'izin') $izinCount++;
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

    private function resolveStatus(?EventAttendance $canonical, $legacyHadir, $legacyIzin): string
    {
        if ($canonical !== null) {
            return $canonical->status === EventAttendance::STATUS_IZIN ? 'izin' : 'hadir';
        }

        if ($legacyHadir !== null) {
            return 'hadir';
        }

        if ($legacyIzin !== null) {
            return 'izin';
        }

        return 'belum';
    }
}
