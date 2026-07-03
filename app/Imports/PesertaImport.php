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
        $kelompok = kelompok::where('kelompok_asal', $row['kelompok'] ?? null)->first();
        $desa = desa::where('desa_asal', $row['desa'] ?? null)->first();
        $jenisKelamin = $row['jenis_kelamin'] ?? null;
        $autoPlacement = peserta::autoPlacement($jenisKelamin);

        return new peserta([
            'nama' => $row['nama'] ?? null,
            'nip' => $autoPlacement['nip'],
            'jenis_kelamin' => $jenisKelamin,
            'regu_id' => $autoPlacement['regu_id'],
            'kelompok_id' => $kelompok ? $kelompok->id : null,
            'desa_id' => $desa ? $desa->id : null,
            'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
        ]);
    }
}
