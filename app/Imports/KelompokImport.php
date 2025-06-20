<?php

namespace App\Imports;

use App\Models\Kelompok;
use Maatwebsite\Excel\Concerns\ToModel;

class KelompokImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Kelompok([
            //
        ]);
    }
}
