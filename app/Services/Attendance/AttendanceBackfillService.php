<?php

namespace App\Services\Attendance;

use App\Models\Absensi;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\SuratIzin;

class AttendanceBackfillService
{
    public function backfill(?int $eventId = null): array
    {
        $hadirResult = $this->backfillHadir($eventId);
        $izinResult = $this->backfillIzin($eventId);
        $suratResult = $this->backfillSuratIzin($eventId);

        return [
            'hadir' => $hadirResult,
            'izin' => $izinResult,
            'surat' => $suratResult,
        ];
    }

    public function backfillHadir(?int $eventId = null): array
    {
        $stats = $this->initStats();

        $query = Absensi::query()
            ->select('absensis.*')
            ->join('sesi_absensis', 'absensis.sesi_id', '=', 'sesi_absensis.id');

        if ($eventId !== null) {
            $query->where('sesi_absensis.event_id', $eventId);
        }

        $query->chunk(200, function ($absensis) use (&$stats) {
            foreach ($absensis as $absensi) {
                $stats['total_scanned']++;

                $result = $this->resolveHadir($absensi);
                $key = $result['key'] ?? 'skip_no_session';
                $stats[$key]++;

                if ($key === 'mapped') {
                    $stats['created']++;
                }
            }
        });

        return $stats;
    }

    public function backfillIzin(?int $eventId = null): array
    {
        $stats = $this->initStats();

        $query = IzinAbsensi::query()
            ->select('izin_absensis.*')
            ->join('sesi_absensis', 'izin_absensis.sesi_id', '=', 'sesi_absensis.id');

        if ($eventId !== null) {
            $query->where('sesi_absensis.event_id', $eventId);
        }

        $query->chunk(200, function ($izins) use (&$stats) {
            foreach ($izins as $izin) {
                $stats['total_scanned']++;

                $result = $this->resolveIzin($izin);
                $key = $result['key'] ?? 'skip_no_session';
                $stats[$key]++;

                if ($key === 'mapped') {
                    $stats['created']++;
                }
            }
        });

        return $stats;
    }

    public function backfillSuratIzin(?int $eventId = null): array
    {
        $stats = [
            'total' => 0,
            'mapped' => 0,
            'updated' => 0,
            'ambiguous' => 0,
            'unmappable' => 0,
            'errors' => 0,
            'skip_existing' => 0,
        ];

        $query = SuratIzin::whereNull('event_id');

        if ($eventId !== null) {
            $query->whereHas('peserta.legacyParticipationMappings', fn ($q) => $q->where('event_id', $eventId));
        }

        $query->chunk(200, function ($surats) use (&$stats) {
            foreach ($surats as $surat) {
                $stats['total']++;
                $eventId = $this->resolveSuratIzinEvent($surat);

                if ($eventId === null) {
                    $stats['unmappable']++;
                    continue;
                }

                if ($eventId === false) {
                    $stats['ambiguous']++;
                    continue;
                }

                $surat->update(['event_id' => $eventId]);
                $stats['updated']++;
                $stats['mapped']++;
            }
        });

        return $stats;
    }

    public function dryRunHadir(?int $eventId = null): array
    {
        $stats = $this->initStats();

        $query = Absensi::query()
            ->select('absensis.*')
            ->join('sesi_absensis', 'absensis.sesi_id', '=', 'sesi_absensis.id');

        if ($eventId !== null) {
            $query->where('sesi_absensis.event_id', $eventId);
        }

        $query->chunk(200, function ($absensis) use (&$stats) {
            foreach ($absensis as $absensi) {
                $stats['total_scanned']++;
                $result = $this->dryRunResolveHadir($absensi);
                $stats[$result['key']]++;
            }
        });

        return $stats;
    }

    public function dryRunIzin(?int $eventId = null): array
    {
        $stats = $this->initStats();

        $query = IzinAbsensi::query()
            ->select('izin_absensis.*')
            ->join('sesi_absensis', 'izin_absensis.sesi_id', '=', 'sesi_absensis.id');

        if ($eventId !== null) {
            $query->where('sesi_absensis.event_id', $eventId);
        }

        $query->chunk(200, function ($izins) use (&$stats) {
            foreach ($izins as $izin) {
                $stats['total_scanned']++;
                $result = $this->dryRunResolveIzin($izin);
                $stats[$result['key']]++;
            }
        });

        return $stats;
    }

    public function dryRunSuratIzin(?int $eventId = null): array
    {
        $stats = [
            'total' => 0,
            'mapped' => 0,
            'updated' => 0,
            'ambiguous' => 0,
            'unmappable' => 0,
            'skip_existing' => 0,
            'errors' => 0,
        ];

        $query = SuratIzin::whereNull('event_id');

        if ($eventId !== null) {
            $query->whereHas('peserta.legacyParticipationMappings', fn ($q) => $q->where('event_id', $eventId));
        }

        $query->chunk(200, function ($surats) use (&$stats) {
            foreach ($surats as $surat) {
                $stats['total']++;

                $resolvedEventId = $this->resolveSuratIzinEvent($surat);

                if ($resolvedEventId === null) {
                    $stats['unmappable']++;
                } elseif ($resolvedEventId === false) {
                    $stats['ambiguous']++;
                } else {
                    $stats['mapped']++;
                }
            }
        });

        return $stats;
    }

    private function resolveHadir(Absensi $absensi): array
    {
        $absensi->loadMissing('sesi');
        $eventId = $absensi->sesi?->event_id;

        if ($eventId === null) {
            return ['key' => 'skip_no_event'];
        }

        $participation = $this->resolveParticipationByNip($absensi->nip, $eventId);

        if ($participation === null) {
            return ['key' => 'skip_no_participation'];
        }

        if ($participation === false) {
            return ['key' => 'skip_ambiguous'];
        }

        $exists = EventAttendance::where('participation_id', $participation->id)
            ->where('sesi_absensi_id', $absensi->sesi_id)
            ->exists();

        if ($exists) {
            return ['key' => 'skip_existing'];
        }

        EventAttendance::create([
            'participation_id' => $participation->id,
            'sesi_absensi_id' => $absensi->sesi_id,
            'event_id' => $eventId,
            'desa_id' => null,
            'status' => EventAttendance::STATUS_HADIR,
            'attended_at' => $absensi->jam_scan,
            'method' => $this->resolveMethod($absensi),
            'metadata' => json_encode([
                'backfilled_from' => 'absensis',
                'original_nip' => $absensi->nip,
                'original_nama' => $absensi->nama,
                'original_absensi_id' => $absensi->id,
            ]),
        ]);

        return ['key' => 'mapped'];
    }

    private function resolveIzin(IzinAbsensi $izin): array
    {
        $izin->loadMissing('sesi');
        $eventId = $izin->sesi?->event_id;

        if ($eventId === null) {
            return ['key' => 'skip_no_event'];
        }

        $participation = $this->resolveParticipationForPeserta($izin->peserta_id, $eventId);

        if ($participation === null) {
            return ['key' => 'skip_no_participation'];
        }

        if ($participation === false) {
            return ['key' => 'skip_ambiguous'];
        }

        $exists = EventAttendance::where('participation_id', $participation->id)
            ->where('sesi_absensi_id', $izin->sesi_id)
            ->exists();

        if ($exists) {
            return ['key' => 'skip_existing'];
        }

        EventAttendance::create([
            'participation_id' => $participation->id,
            'sesi_absensi_id' => $izin->sesi_id,
            'event_id' => $eventId,
            'desa_id' => null,
            'status' => EventAttendance::STATUS_IZIN,
            'attended_at' => $izin->created_at,
            'method' => $izin->source === 'surat_izin' ? 'surat_izin' : 'izin',
            'metadata' => json_encode([
                'backfilled_from' => 'izin_absensis',
                'original_izin_id' => $izin->id,
                'original_source' => $izin->source,
                'surat_izin_id' => $izin->surat_izin_id,
            ]),
        ]);

        return ['key' => 'mapped'];
    }

    private function dryRunResolveHadir(Absensi $absensi): array
    {
        $absensi->loadMissing('sesi');
        $eventId = $absensi->sesi?->event_id;

        if ($eventId === null) {
            return ['key' => 'skip_no_event'];
        }

        $participation = $this->resolveParticipationByNip($absensi->nip, $eventId);

        if ($participation === null) {
            return ['key' => 'skip_no_participation'];
        }

        if ($participation === false) {
            return ['key' => 'skip_ambiguous'];
        }

        $exists = EventAttendance::where('participation_id', $participation->id)
            ->where('sesi_absensi_id', $absensi->sesi_id)
            ->exists();

        if ($exists) {
            return ['key' => 'skip_existing'];
        }

        return ['key' => 'mapped'];
    }

    private function dryRunResolveIzin(IzinAbsensi $izin): array
    {
        $izin->loadMissing('sesi');
        $eventId = $izin->sesi?->event_id;

        if ($eventId === null) {
            return ['key' => 'skip_no_event'];
        }

        $participation = $this->resolveParticipationForPeserta($izin->peserta_id, $eventId);

        if ($participation === null) {
            return ['key' => 'skip_no_participation'];
        }

        if ($participation === false) {
            return ['key' => 'skip_ambiguous'];
        }

        $exists = EventAttendance::where('participation_id', $participation->id)
            ->where('sesi_absensi_id', $izin->sesi_id)
            ->exists();

        if ($exists) {
            return ['key' => 'skip_existing'];
        }

        return ['key' => 'mapped'];
    }

    private function resolveParticipationByNip(int $nip, int $eventId): Participation|null|false
    {
        $pesertaMapping = LegacyPesertaMapping::where('legacy_nip', (string) $nip)->first();

        if ($pesertaMapping === null) {
            return null;
        }

        $mappings = LegacyParticipationMapping::where('peserta_id', $pesertaMapping->peserta_id)
            ->where('event_id', $eventId)
            ->get();

        if ($mappings->isEmpty()) {
            return null;
        }

        if ($mappings->count() > 1) {
            return false;
        }

        return $mappings->first()->participation;
    }

    private function resolveParticipationForPeserta(int $pesertaId, int $eventId): Participation|null|false
    {
        $mappings = LegacyParticipationMapping::where('peserta_id', $pesertaId)
            ->where('event_id', $eventId)
            ->get();

        if ($mappings->isEmpty()) {
            return null;
        }

        if ($mappings->count() > 1) {
            return false;
        }

        return $mappings->first()->participation;
    }

    private function resolveSuratIzinEvent(SuratIzin $surat): int|null|false
    {
        $surat->loadMissing('izinAbsensis.sesi');

        $eventIds = $surat->izinAbsensis
            ->pluck('sesi.event_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (count($eventIds) === 0) {
            $mapping = LegacyParticipationMapping::where('peserta_id', $surat->peserta_id)->first();

            return $mapping !== null ? (int) $mapping->event_id : null;
        }

        if (count($eventIds) === 1) {
            return (int) $eventIds[0];
        }

        return false;
    }

    private function resolveMethod(Absensi $absensi): string
    {
        return 'scan';
    }

    private function initStats(): array
    {
        return [
            'total_scanned' => 0,
            'mapped' => 0,
            'created' => 0,
            'skip_existing' => 0,
            'skip_no_session' => 0,
            'skip_no_event' => 0,
            'skip_no_peserta' => 0,
            'skip_no_mapping' => 0,
            'skip_no_participation' => 0,
            'skip_ambiguous' => 0,
            'errors' => 0,
        ];
    }
}
