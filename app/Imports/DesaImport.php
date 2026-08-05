<?php

namespace App\Imports;

use App\Models\desa;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DesaImport implements ToModel, WithCustomCsvSettings, WithHeadingRow
{
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Desa([
            'desa_asal' => $row['desa'],
        ]);
    }
}
