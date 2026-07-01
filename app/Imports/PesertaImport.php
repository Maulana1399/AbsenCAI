<?php

namespace App\Imports;

use App\Models\desa;
use App\Models\kelompok;
use App\Models\peserta;
use App\Models\regu;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PesertaImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $regu = regu::where('regu', $row['regu'] ?? null)->first();
        $kelompok = kelompok::where('kelompok_asal', $row['kelompok'] ?? null)->first();
        $desa = desa::where('desa_asal', $row['desa'] ?? null)->first();

        return new peserta([
            'nama' => $row['nama'] ?? null,
            'nip' => $row['nip'] ?? null,
            'jenis_kelamin' => $row['jenis_kelamin'] ?? null,
            'regu_id' => $regu ? $regu->id : null,
            'kelompok_id' => $kelompok ? $kelompok->id : null,
            'desa_id' => $desa ? $desa->id : null,
            'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
        ]);
    }
}
