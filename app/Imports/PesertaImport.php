<?php

namespace App\Imports;

use App\Models\desa;
use App\Models\kelompok;
use App\Models\peserta;
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


        // cegah import peserta yang sama
        if (
            peserta::where('nama', $row['nama'])
                ->where('desa_id', $desa?->id)
                ->where('kelompok_id', $kelompok?->id)
                ->exists()
        ) {
            return null;
        }


        $jenisKelamin = $row['jenis_kelamin'] ?? null;


        // generate NIP + regu
        $autoPlacement = peserta::autoPlacement(
            $jenisKelamin
        );


        return new peserta([

            'nama' => trim($row['nama']),

            'nip' => $autoPlacement['nip'],

            'jenis_kelamin' => $jenisKelamin,

            'jenis_peserta' => $row['jenis_peserta']
                ?? peserta::JENIS_KIRIMAN,

            'regu_id' => $autoPlacement['regu_id'],

            'kelompok_id' => $kelompok?->id,

            'desa_id' => $desa?->id,

            'status_registrasi'
                => peserta::STATUS_BELUM_REGISTRASI,
        ]);
    }
}