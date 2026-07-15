<?php

namespace App\Services\Attendance;

use App\Models\Absensi;
use App\Models\peserta;
use App\Models\SesiAbsensi;
use Carbon\Carbon;

class AttendanceService
{
    public function processScan(string $nip, ?int $sesiId = null): array
    {
        $nip = trim($nip);

        $peserta = peserta::where('nip', $nip)->first();

        if (! $peserta) {
            return [
                'status' => 'not_found',
                'message' => 'Data peserta tidak ditemukan!',
            ];
        }

        $sesi = $sesiId ? SesiAbsensi::find($sesiId) : SesiAbsensi::where('aktif', true)->first();

        if (! $sesi) {
            return [
                'status' => 'session_required',
                'message' => 'Pilih sesi absensi terlebih dahulu',
            ];
        }

        $last = Absensi::where('nip', $nip)
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
}
