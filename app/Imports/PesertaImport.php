<?php

namespace App\Imports;

use App\Models\peserta;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class PesertaImport implements ToModel, WithHeadingRow
{
    
    public function model(array $row)
    {
        $regu = Regu::where('regu', $row['regu'])->first();
        $kelompok = Kelompok::where('kelompok_asal', $row['kelompok'])->first();
        $desa = Desa::where('desa_asal', $row['desa'])->first();

        return new Peserta([
            'nama' => $row['nama'],
            'nip' => $row['nip'],
            'jenis_kelamin' => $row['jenis_kelamin'],
            'regu_id' => $regu ? $regu->id : null,
            'kelompok_id' => $kelompok ? $kelompok->id : null,
            'desa_id' => $desa ? $desa->id : null,
        ]);
    }
}

<?php

namespace App\Imports;


use App\Models\peserta;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class PesertaImport implements ToModel, WithHeadingRow
{
    
    public function model(array $row)
    {
        $regu = Regu::where('regu', $row['regu'])->first();
        $kelompok = Kelompok::where('kelompok_asal', $row['kelompok'])->first();
        $desa = Desa::where('desa_asal', $row['desa'])->first();

        return new Peserta([
            'nama' => $row['nama'],
            'nip' => $row['nip'],
            'jenis_kelamin' => $row['jenis_kelamin'],
            'regu_id' => $regu ? $regu->id : null,
            'kelompok_id' => $kelompok ? $kelompok->id : null,
            'desa_id' => $desa ? $desa->id : null,
        ]);
    }
}
