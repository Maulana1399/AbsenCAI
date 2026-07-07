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
        $kelompok = kelompok::where(
            'kelompok_asal',
            $row['kelompok'] ?? null
        )->first();

        $desa = desa::where(
            'desa_asal',
            $row['desa'] ?? null
        )->first();


        // cek peserta sudah ada
        if (
            peserta::where('nama', $row['nama'] ?? null)
                ->where('desa_id', $desa?->id)
                ->where('kelompok_id', $kelompok?->id)
                ->exists()
        ) {
            return null;
        }


        $jenisKelamin = $row['jenis_kelamin'] ?? null;


        // generate NIP + regu setelah yakin peserta baru
        $autoPlacement = peserta::autoPlacement(
            $jenisKelamin
        );


        return new peserta([

            'nama' => $row['nama'] ?? null,

            'nip' => $autoPlacement['nip'],

            'jenis_kelamin' => $jenisKelamin,

            'jenis_peserta' => $row['jenis_peserta']
                ?? peserta::JENIS_WAJIB,

            'regu_id' => $autoPlacement['regu_id'],

            'kelompok_id' => $kelompok?->id,

            'desa_id' => $desa?->id,

            'status_registrasi'
                => peserta::STATUS_BELUM_REGISTRASI,
        ]);
    }
}