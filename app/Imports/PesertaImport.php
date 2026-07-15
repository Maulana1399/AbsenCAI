<?php

namespace App\Imports;

use App\Models\desa;
use App\Models\kelompok;
use App\Models\peserta;
use App\Services\Placement\PlacementService;
use App\Services\Registration\RegistrationService;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PesertaImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // skip baris kosong excel
        if (
            empty($row['nama'])
        ) {
            return null;
        }


        $kelompok = kelompok::whereRaw(
            'LOWER(TRIM(kelompok_asal)) = ?',
            [
                strtolower(trim($row['kelompok'] ?? ''))
            ]
        )->first();


        $desa = desa::whereRaw(
            'LOWER(TRIM(desa_asal)) = ?',
            [
                strtolower(trim($row['desa'] ?? ''))
            ]
        )->first();


        $jenisKelamin = $row['jenis_kelamin'] ?? null;
        $autoPlacement = PlacementService::autoPlacement($jenisKelamin);

        return app(RegistrationService::class)->createParticipant([
            'nama' => trim($row['nama']),
            'nip' => $autoPlacement['nip'],
            'participant_number' => PlacementService::generateParticipantNumber($jenisKelamin),
            'jenis_kelamin' => $jenisKelamin,
            'jenis_peserta' => $row['jenis_peserta']
                ?? peserta::JENIS_KIRIMAN,
            'regu_id' => $autoPlacement['regu_id'],
            'kelompok_id' => $kelompok?->id,
            'desa_id' => $desa?->id,
            'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
            'attendance_code' => null,
        ]);
    }
}