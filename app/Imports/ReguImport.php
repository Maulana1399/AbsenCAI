<?php
namespace App\Imports;

use App\Models\Regu;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ReguImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Regu([
            'regu' => $row['regu'],
        ]);
    }
}
