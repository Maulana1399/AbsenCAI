<?php

namespace App\Services\Attendance;

use App\Models\Absensi;
use App\Models\IzinAbsensi;
use App\Models\LegacyPesertaMapping;
use App\Models\peserta;
use App\Models\SesiAbsensi;
use App\Support\ActiveEventContext;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function processScan(string $identifier, ?int $sesiId = null): array
    {
        $identifier = trim($identifier);

        $activeEvent = app(ActiveEventContext::class)->requireCurrent();
        $peserta = $this->findParticipant($identifier);

        if (! $peserta) {
            return [
                'status' => 'not_found',
                'message' => 'Data peserta tidak ditemukan!',
            ];
        }

        if (! $this->isParticipantAllowedForEvent($peserta, $activeEvent->id)) {
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

        if (! $sesi) {
            return [
                'status' => 'session_required',
                'message' => 'Pilih sesi absensi terlebih dahulu',
            ];
        }

        if (IzinAbsensi::where('peserta_id', $peserta->id)->where('sesi_id', $sesi->id)->exists()) {
            throw ValidationException::withMessages([
                'peserta' => 'Peserta sedang berstatus izin pada sesi ini.',
            ]);
        }

        $last = Absensi::where('nip', $peserta->nip)
            ->where('sesi_id', $sesi->id)
            ->first();

        if ($last) {
            return [
                'status' => 'duplicate',
                'message' => 'Peserta sudah absen pada sesi ini',
                'peserta' => $peserta,
                'sesi' => $sesi,
            ];
        }

        $jamScan = Carbon::now()->format('Y-m-d H:i:s');

        $absensi = Absensi::create([
            'nip' => $peserta->nip,
            'nama' => $peserta->nama,
            'jam_scan' => $jamScan,
            'sesi_id' => $sesi->id,
        ]);

        return [
            'status' => 'success',
            'message' => 'Absensi berhasil!',
            'peserta' => $peserta,
            'sesi' => $sesi,
            'jam_scan' => $jamScan,
            'absensi' => $absensi,
        ];
    }

    private function findParticipant(string $identifier): ?peserta
    {
        $byAttendanceCode = peserta::whereRaw('LOWER(attendance_code) = ?', [strtolower($identifier)])->first();

        if ($byAttendanceCode) {
            return $byAttendanceCode;
        }

        return peserta::where('nip', $identifier)->first();
    }

    private function isParticipantAllowedForEvent(peserta $peserta, int $eventId): bool
    {
        $mapping = LegacyPesertaMapping::with('participation')
            ->where('peserta_id', $peserta->id)
            ->first();

        if ($mapping === null || $mapping->participation === null) {
            return false;
        }

        if ((int) $mapping->peserta_id !== (int) $peserta->id) {
            return false;
        }

        if ((int) $mapping->event_id !== $eventId) {
            return false;
        }

        if ((int) $mapping->participation->event_id !== $eventId) {
            return false;
        }

        if ((int) $mapping->participation->person_id !== (int) $mapping->person_id) {
            return false;
        }

        return true;
    }
}
