<?php

namespace App\Imports;

use App\Models\desa;
use App\Models\kelompok;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KelompokImport implements ToModel , WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {

        $desa = desa::where('desa_asal', $row['desa'])->first();

        return new Kelompok([
            'kelompok_asal' => $row['kelompok'],
            'desa_id' => $desa ? $desa->id : null,

        ]);
    }
}
