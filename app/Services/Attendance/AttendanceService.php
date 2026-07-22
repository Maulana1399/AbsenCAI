<?php

namespace App\Services\Attendance;

use App\Models\Absensi;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\LegacyPesertaMapping;
use App\Services\Attendance\LegacyParticipationResolver;
use App\Models\Participation;
use App\Models\peserta;
use App\Models\SesiAbsensi;
use App\Support\ActiveEventContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function processScan(string $identifier, ?int $sesiId = null, string $method = 'scan'): array
    {
        if (!in_array($method, ['scan', 'manual'], true)) {
            throw new \InvalidArgumentException("Invalid attendance method: {$method}");
        }

        $identifier = trim($identifier);
        $activeEvent = app(ActiveEventContext::class)->requireCurrent();

        $identity = $this->resolveIdentity($identifier, $activeEvent->id);

        if (!$identity->isResolved()) {
            return [
                'status' => 'not_found',
                'message' => 'Data peserta tidak ditemukan!',
            ];
        }

        if ($identity->isCanonical() && $identity->participation->event_id != $activeEvent->id) {
            return [
                'status' => 'not_found',
                'message' => 'Data peserta tidak ditemukan!',
            ];
        }

        $sesi = $sesiId
            ? SesiAbsensi::where('event_id', $activeEvent->id)->find($sesiId)
            : SesiAbsensi::where('event_id', $activeEvent->id)
                ->where('aktif', true)
                ->first();

        if (!$sesi) {
            return [
                'status' => 'session_required',
                'message' => 'Pilih sesi absensi terlebih dahulu',
            ];
        }

        if ($identity->isCanonical()) {
            $existing = EventAttendance::where('participation_id', $identity->participationId)
                ->where('sesi_absensi_id', $sesi->id)
                ->first();

            if ($existing) {
                if ($existing->status === EventAttendance::STATUS_IZIN) {
                    throw ValidationException::withMessages([
                        'peserta' => 'Peserta sedang berstatus izin pada sesi ini.',
                    ]);
                }

                return [
                    'status' => 'duplicate',
                    'message' => 'Peserta sudah absen pada sesi ini',
                    'identity' => $identity,
                    'peserta' => $identity->peserta,
                    'sesi' => $sesi,
                ];
            }
        }

        if ($identity->isLegacy()) {
            if (IzinAbsensi::where('peserta_id', $identity->pesertaId)->where('sesi_id', $sesi->id)->exists()) {
                throw ValidationException::withMessages([
                    'peserta' => 'Peserta sedang berstatus izin pada sesi ini.',
                ]);
            }

            $lastAbsensi = Absensi::where('nip', $identity->nip)
                ->where('sesi_id', $sesi->id)
                ->first();

            if ($lastAbsensi) {
                return [
                    'status' => 'duplicate',
                    'message' => 'Peserta sudah absen pada sesi ini',
                    'identity' => $identity,
                    'peserta' => $identity->peserta,
                    'sesi' => $sesi,
                ];
            }
        }

        $now = Carbon::now();
        $jamScan = $now->format('Y-m-d H:i:s');
        $writeLegacy = config('features.attendance_legacy_write', true);
        $mustWriteLegacy = $identity->isLegacy() && !$identity->isCanonical();

        $absensi = DB::transaction(function () use ($identity, $sesi, $jamScan, $now, $activeEvent, $method, $writeLegacy, $mustWriteLegacy) {
            $a = null;

            if (($writeLegacy || $mustWriteLegacy) && $identity->isLegacy() && $identity->pesertaId) {
                $a = Absensi::create([
                    'nip' => $identity->nip,
                    'nama' => $identity->nama,
                    'jam_scan' => $jamScan,
                    'sesi_id' => $sesi->id,
                ]);
            }

            if ($identity->isCanonical()) {
                EventAttendance::create([
                    'participation_id' => $identity->participationId,
                    'sesi_absensi_id' => $sesi->id,
                    'event_id' => $activeEvent->id,
                    'status' => EventAttendance::STATUS_HADIR,
                    'attended_at' => $now,
                    'method' => $method,
                    'recorded_by' => auth()->id(),
                ]);
            }

            return $a;
        });

        return [
            'status' => 'success',
            'message' => 'Absensi berhasil!',
            'identity' => $identity,
            'peserta' => $identity->peserta,
            'sesi' => $sesi,
            'jam_scan' => $jamScan,
            'absensi' => $absensi,
        ];
    }

    public function resolveIdentity(string $identifier, int $eventId): AttendanceIdentity
    {
        $participation = Participation::with('person')
            ->where('event_id', $eventId)
            ->whereRaw('LOWER(attendance_code) = ?', [strtolower($identifier)])
            ->first();

        $resolver = app(LegacyParticipationResolver::class);

        if ($participation) {
            $peserta = $resolver->resolvePesertaByParticipation($participation->id, $eventId);
            return new AttendanceIdentity(
                participation: $participation,
                peserta: $peserta,
                person: $participation->person,
            );
        }

        $pesertaByCode = peserta::whereRaw('LOWER(attendance_code) = ?', [strtolower($identifier)])->first();

        if ($pesertaByCode) {
            $participationByCode = $resolver->resolveByPesertaAndEvent($pesertaByCode->id, $eventId);

            if ($participationByCode) {
                $pesertaByCodeResolved = $resolver->resolvePesertaByParticipation($participationByCode->id, $eventId);
                return new AttendanceIdentity(
                    participation: $participationByCode,
                    peserta: $pesertaByCodeResolved ?? $pesertaByCode,
                    person: $participationByCode->person,
                );
            }
        }

        $participationByLegacyCode = $resolver->resolveByLegacyAttendanceCode($identifier, $eventId);
        if ($participationByLegacyCode) {
            $pesertaByLegacy = $resolver->resolvePesertaByParticipation($participationByLegacyCode->id, $eventId);
            return new AttendanceIdentity(
                participation: $participationByLegacyCode,
                peserta: $pesertaByLegacy,
                person: $participationByLegacyCode->person,
            );
        }

        $pesertaByNip = peserta::where('nip', $identifier)->first();

        if ($pesertaByNip) {
            $participationByNip = $resolver->resolveByLegacyNip($identifier, $eventId);

            if ($participationByNip) {
                $pesertaByNipResolved = $resolver->resolvePesertaByParticipation($participationByNip->id, $eventId);
                return new AttendanceIdentity(
                    participation: $participationByNip,
                    peserta: $pesertaByNipResolved ?? $pesertaByNip,
                    person: $participationByNip->person,
                );
            }

            return new AttendanceIdentity(
                participation: null,
                peserta: null,
                person: null,
            );
        }

        return new AttendanceIdentity(
            participation: null,
            peserta: null,
            person: null,
        );
    }
}
