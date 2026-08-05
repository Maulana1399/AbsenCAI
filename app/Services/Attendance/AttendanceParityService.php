<?php

namespace App\Services\Attendance;

use App\Models\Absensi;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\SesiAbsensi;

class AttendanceParityService
{
    public function audit(int $eventId, string $mode = 'migration'): array
    {
        $event = Event::findOrFail($eventId);

        $legacyHadirRecords = Absensi::whereHas('sesi', fn ($q) => $q->where('event_id', $eventId))->get();
        $legacyIzinRecords = IzinAbsensi::whereHas('sesi', fn ($q) => $q->where('event_id', $eventId))->get();
        $canonicalRecords = EventAttendance::where('event_id', $eventId)
            ->whereNotNull('sesi_absensi_id')
            ->get();

        $resolver = app(ParticipationResolver::class);

        $matchedHadir = 0;
        $matchedIzin = 0;
        $missingCanonicalHadir = 0;
        $missingCanonicalIzin = 0;
        $unmappableHadir = 0;
        $unmappableIzin = 0;
        $statusConflicts = 0;

        foreach ($legacyHadirRecords as $absensi) {
            $participation = $resolver->resolveByNip((int) $absensi->nip, $eventId);

            if ($participation === null) {
                $unmappableHadir++;

                continue;
            }

            $canonical = $canonicalRecords
                ->where('participation_id', $participation->id)
                ->where('sesi_absensi_id', $absensi->sesi_id)
                ->first();

            if ($canonical === null) {
                $missingCanonicalHadir++;

                continue;
            }

            if ($canonical->status !== EventAttendance::STATUS_HADIR) {
                $statusConflicts++;

                continue;
            }

            $matchedHadir++;
        }

        foreach ($legacyIzinRecords as $izin) {
            $participation = $resolver->resolveByPesertaId($izin->peserta_id, $eventId);

            if ($participation === null) {
                $unmappableIzin++;

                continue;
            }

            $canonical = $canonicalRecords
                ->where('participation_id', $participation->id)
                ->where('sesi_absensi_id', $izin->sesi_id)
                ->first();

            if ($canonical === null) {
                $missingCanonicalIzin++;

                continue;
            }

            if ($canonical->status !== EventAttendance::STATUS_IZIN) {
                $statusConflicts++;

                continue;
            }

            $matchedIzin++;
        }

        $canonicalHadirCount = $canonicalRecords->where('status', EventAttendance::STATUS_HADIR)->count();
        $canonicalIzinCount = $canonicalRecords->where('status', EventAttendance::STATUS_IZIN)->count();

        if ($mode === 'canonical') {
            $orphanHadir = 0;
            $orphanIzin = 0;
        } else {
            $orphanHadir = $canonicalHadirCount - $matchedHadir - ($statusConflicts > 0 ? 1 : 0);
            if ($orphanHadir < 0) {
                $orphanHadir = 0;
            }

            $orphanIzin = $canonicalIzinCount - $matchedIzin;
            if ($orphanIzin < 0) {
                $orphanIzin = 0;
            }
        }

        $legacyHadirCount = $legacyHadirRecords->count();
        $legacyIzinCount = $legacyIzinRecords->count();
        $legacyTotal = $legacyHadirCount + $legacyIzinCount;
        $canonicalTotal = $canonicalHadirCount + $canonicalIzinCount;
        $matched = $matchedHadir + $matchedIzin;
        $expectedMappable = $legacyTotal - $unmappableHadir - $unmappableIzin;
        $parityPercentage = $expectedMappable > 0
            ? round(($matched / $expectedMappable) * 100, 2)
            : ($canonicalTotal === 0 ? 100.0 : 0.0);

        return [
            'event_id' => $eventId,
            'event_name' => $event->name,

            'legacy_hadir_count' => $legacyHadirCount,
            'legacy_izin_count' => $legacyIzinCount,
            'legacy_total' => $legacyTotal,

            'canonical_hadir_count' => $canonicalHadirCount,
            'canonical_izin_count' => $canonicalIzinCount,
            'canonical_total' => $canonicalTotal,

            'matched_hadir' => $matchedHadir,
            'matched_izin' => $matchedIzin,
            'matched' => $matched,

            'missing_canonical_hadir' => $missingCanonicalHadir,
            'missing_canonical_izin' => $missingCanonicalIzin,
            'missing_canonical' => $missingCanonicalHadir + $missingCanonicalIzin,

            'orphan_canonical_hadir' => $orphanHadir,
            'orphan_canonical_izin' => $orphanIzin,
            'orphan_canonical' => $orphanHadir + $orphanIzin,

            'unmappable_hadir' => $unmappableHadir,
            'unmappable_izin' => $unmappableIzin,
            'unmappable' => $unmappableHadir + $unmappableIzin,

            'status_conflicts' => $statusConflicts,

            'expected_mappable' => $expectedMappable,
            'parity_percentage' => $parityPercentage,
            'audit_mode' => $mode,
        ];
    }

    public function auditAll(string $mode = 'migration'): array
    {
        $eventIds = SesiAbsensi::whereNotNull('event_id')
            ->distinct()
            ->pluck('event_id');

        $results = [];
        $totals = [
            'legacy_total' => 0,
            'canonical_total' => 0,
            'matched' => 0,
            'missing_canonical' => 0,
            'orphan_canonical' => 0,
            'unmappable' => 0,
            'status_conflicts' => 0,
            'expected_mappable' => 0,
        ];

        foreach ($eventIds as $eventId) {
            $result = $this->audit($eventId, $mode);
            $results[] = $result;

            $totals['legacy_total'] += $result['legacy_total'];
            $totals['canonical_total'] += $result['canonical_total'];
            $totals['matched'] += $result['matched'];
            $totals['missing_canonical'] += $result['missing_canonical'];
            $totals['orphan_canonical'] += $result['orphan_canonical'];
            $totals['unmappable'] += $result['unmappable'];
            $totals['status_conflicts'] += $result['status_conflicts'];
            $totals['expected_mappable'] += $result['expected_mappable'];
        }

        $totals['parity_percentage'] = $totals['expected_mappable'] > 0
            ? round(($totals['matched'] / $totals['expected_mappable']) * 100, 2)
            : (count($results) === 0 ? 100.0 : 0.0);

        return [
            'events' => $results,
            'totals' => $totals,
            'audit_mode' => $mode,
        ];
    }
}
