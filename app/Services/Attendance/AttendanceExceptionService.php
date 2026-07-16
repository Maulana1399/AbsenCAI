<?php

namespace App\Services\Attendance;

use App\Models\IzinAbsensi;
use App\Models\SesiAbsensi;
use App\Models\peserta;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class AttendanceExceptionService
{
    public function recordIzin(int $pesertaId, int $sesiId, string $source = 'manual'): IzinAbsensi
    {
        $peserta = peserta::findOrFail($pesertaId);
        $sesi = SesiAbsensi::findOrFail($sesiId);

        if (IzinAbsensi::where('peserta_id', $peserta->id)->where('sesi_id', $sesi->id)->exists()) {
            throw ValidationException::withMessages([
                'peserta' => 'Peserta sudah berstatus izin pada sesi ini.',
            ]);
        }

        if (\App\Models\Absensi::where('nip', $peserta->nip)->where('sesi_id', $sesi->id)->exists()) {
            throw ValidationException::withMessages([
                'peserta' => 'Peserta sudah hadir pada sesi ini.',
            ]);
        }

        try {
            return IzinAbsensi::create([
                'peserta_id' => $peserta->id,
                'sesi_id' => $sesi->id,
                'status' => 'izin',
                'source' => $source,
            ]);
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
