<?php

namespace Database\Seeders;

use App\Models\desa;
use Illuminate\Database\Seeder;

class DesaSeeder extends Seeder
{
    public function run(): void
    {
        $desas = [
            'Batam',
            'Ringroad',
            'Sepinggan',
            'Timur Raya',
        ];

        foreach ($desas as $name) {
            desa::firstOrCreate(['desa_asal' => $name]);
        }
    }
}
