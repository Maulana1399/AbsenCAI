<?php

namespace Database\Seeders;

use App\Models\desa;
use App\Models\kelompok;
use Illuminate\Database\Seeder;

class KelompokSeeder extends Seeder
{
    public function run(): void
    {
        $kelompokByDesa = [
            'Batam' => [
                'KM 7',
                'KM 10',
                'Perumnas',
                'Kariangau',
                'Soekarno Hatta',
                'Somber',
            ],
            'Ring Road' => [
                'Gunung Samarinda',
                'Sumber Rejo',
                'Bandara Utara',
            ],
            'Sepinggan' => [
                'Sepinggan 1',
                'Sepinggan 2',
                'Sepinggan 3',
                'Sepinggan 4',
                'Bandara Baru',
                'Melati',
            ],
            'Timur Raya' => [
                'Lemaru',
                'Batakan',
                'Lemaru Sosial',
                'Manggar',
            ],
        ];

        foreach ($kelompokByDesa as $desaName => $kelompokNames) {
            $desa = desa::where('desa_asal', $desaName)->first();

            if ($desa === null) {
                continue;
            }

            foreach ($kelompokNames as $kelompokName) {
                kelompok::firstOrCreate(
                    ['kelompok_asal' => $kelompokName],
                    ['desa_id' => $desa->id],
                );
            }
        }
    }
}
