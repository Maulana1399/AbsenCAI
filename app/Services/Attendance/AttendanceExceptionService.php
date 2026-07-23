<?php

namespace App\Services\Attendance;

use App\Models\Absensi;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\Participation;
use App\Models\SesiAbsensi;
use App\Models\peserta;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceExceptionService
{
    public function recordIzin(
        ?int $pesertaId = null,
        int $sesiId,
        string $source = 'manual',
        ?int $suratIzinId = null,
        ?int $participationId = null,
    ): IzinAbsensi|EventAttendance {
        $sesi = SesiAbsensi::findOrFail($sesiId);
        $eventId = $sesi->event_id;

        $resolvedParticipation = null;

        if ($participationId) {
            $part = Participation::with('person.legacyPesertaMapping.peserta')->find($participationId);
            if (! $part) {
                throw ValidationException::withMessages([
                    'participation' => 'Participation tidak ditemukan.',
                ]);
            }
            if ((int) $part->event_id !== (int) $eventId) {
                throw ValidationException::withMessages([
                    'participation' => 'Participation tidak sesuai dengan event sesi ini.',
                ]);
            }
            $resolvedParticipation = $part;
        } elseif ($pesertaId) {
            $peserta = peserta::findOrFail($pesertaId);

            if (IzinAbsensi::where('peserta_id', $peserta->id)->where('sesi_id', $sesi->id)->exists()) {
                throw ValidationException::withMessages([
                    'peserta' => 'Peserta sudah berstatus izin pada sesi ini.',
                ]);
            }

            if (Absensi::where('nip', $peserta->nip)->where('sesi_id', $sesi->id)->exists()) {
                throw ValidationException::withMessages([
                    'peserta' => 'Peserta sudah hadir pada sesi ini.',
                ]);
            }

            $resolvedParticipation = $eventId !== null
                ? app(ParticipationResolver::class)->resolveByPeserta($peserta, $eventId)
                : null;

            // Reject cross-event: peserta has mappings but none for this event's session
            if ($resolvedParticipation === null && $eventId !== null) {
                $anyMapping = \App\Models\LegacyParticipationMapping::where('peserta_id', $peserta->id)->exists();
                $eventMapping = \App\Models\LegacyParticipationMapping::where('peserta_id', $peserta->id)
                    ->where('event_id', $eventId)
                    ->exists();
                if ($anyMapping && !$eventMapping) {
                    throw ValidationException::withMessages([
                        'peserta' => 'Peserta tidak terdaftar pada event sesi ini.',
                    ]);
                }
            }
        } else {
            throw ValidationException::withMessages([
                'peserta' => 'Peserta ID atau Participation ID wajib diisi.',
            ]);
        }

        if ($resolvedParticipation !== null) {
            $existing = EventAttendance::where('participation_id', $resolvedParticipation->id)
                ->where('sesi_absensi_id', $sesi->id)
                ->first();

            if ($existing !== null) {
                if ($existing->status === EventAttendance::STATUS_IZIN) {
                    throw ValidationException::withMessages([
                        'peserta' => 'Peserta sudah berstatus izin pada sesi ini.',
                    ]);
                }
                if ($existing->status === EventAttendance::STATUS_HADIR) {
                    throw ValidationException::withMessages([
                        'peserta' => 'Peserta sudah hadir pada sesi ini.',
                    ]);
                }
            }
        }

        try {
            $writeLegacy = config('features.attendance_legacy_write', true);

            return DB::transaction(function () use ($pesertaId, $sesi, $source, $suratIzinId, $resolvedParticipation, $writeLegacy, $eventId) {
                if ($resolvedParticipation !== null) {
                    EventAttendance::create([
                        'participation_id' => $resolvedParticipation->id,
                        'sesi_absensi_id'  => $sesi->id,
                        'event_id'         => $eventId,
                        'status'           => EventAttendance::STATUS_IZIN,
                        'attended_at'      => now(),
                        'method'           => $source === 'surat_izin' ? 'surat_izin' : 'izin',
                        'recorded_by'      => auth()->id(),
                    ]);
                }

                $writeLegacyForParticipant = $writeLegacy || $resolvedParticipation === null;

                if ($writeLegacyForParticipant && $pesertaId) {
                    $peserta = $pesertaId ? \App\Models\peserta::find($pesertaId) : null;
                    if ($peserta) {
                        return IzinAbsensi::create([
                            'peserta_id'    => $peserta->id,
                            'sesi_id'       => $sesi->id,
                            'status'        => 'izin',
                            'source'        => $source,
                            'surat_izin_id' => $suratIzinId,
                        ]);
                    }
                }

                if ($resolvedParticipation !== null) {
                    return EventAttendance::where('participation_id', $resolvedParticipation->id)
                        ->where('sesi_absensi_id', $sesi->id)
                        ->first();
                }

                throw ValidationException::withMessages([
                    'peserta' => 'Tidak dapat membuat izin: peserta tidak ditemukan dan tidak ada participation.',
                ]);
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                throw ValidationException::withMessages([
                    'peserta' => 'Peserta sudah berstatus izin pada sesi ini.',
                ]);
            }
            throw $exception;
        }
    }
}
