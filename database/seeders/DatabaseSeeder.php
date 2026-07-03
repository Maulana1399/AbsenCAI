<?php

namespace Database\Seeders;

use App\Models\desa;
use App\Models\kelompok;
use App\Models\peserta;
use App\Models\regu;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => 'password',
        ]);

        $desaA = desa::updateOrCreate([
            'desa_asal' => 'Desa A',
        ]);

        $desaB = desa::updateOrCreate([
            'desa_asal' => 'Desa B',
        ]);

        $kelompokA = kelompok::updateOrCreate([
            'kelompok_asal' => 'Kelompok A',
        ], [
            'desa_id' => $desaA->id,
        ]);

        $kelompokB = kelompok::updateOrCreate([
            'kelompok_asal' => 'Kelompok B',
        ], [
            'desa_id' => $desaB->id,
        ]);

        $reguData = [
            ['regu' => 'Biru', 'jenis_kelamin' => 'Laki - Laki'],
            ['regu' => 'Merah', 'jenis_kelamin' => 'Laki - Laki'],
            ['regu' => 'Biru', 'jenis_kelamin' => 'Perempuan'],
            ['regu' => 'Merah', 'jenis_kelamin' => 'Perempuan'],
        ];

        $regus = [];
        $hasReguGender = Schema::hasColumn('regus', 'jenis_kelamin');

        if ($hasReguGender) {
            regu::whereNull('jenis_kelamin')
                ->where('regu', 'like', '%Laki - Laki%')
                ->update(['jenis_kelamin' => 'Laki - Laki']);

            regu::whereNull('jenis_kelamin')
                ->where('regu', 'like', '%Perempuan%')
                ->update(['jenis_kelamin' => 'Perempuan']);
        }

        foreach ($reguData as $item) {
            $name = $item['regu'].' '.$item['jenis_kelamin'];
            $attributes = ['regu' => $name];

            if ($hasReguGender) {
                $attributes['jenis_kelamin'] = $item['jenis_kelamin'];
            }

            $regus[$name] = regu::updateOrCreate(['regu' => $name], $attributes);
        }

        $maleRegus = [
            $regus['Biru Laki - Laki'],
            $regus['Merah Laki - Laki'],
        ];

        $femaleRegus = [
            $regus['Biru Perempuan'],
            $regus['Merah Perempuan'],
        ];

        for ($i = 1; $i <= 5; $i++) {
            $regu = $maleRegus[($i - 1) % count($maleRegus)];

            peserta::updateOrCreate(
                ['nip' => 900000 + $i],
                [
                    'nama' => 'Peserta Laki '.$i,
                    'nip' => 900000 + $i,
                    'jenis_kelamin' => 'Laki - Laki',
                    'kelompok_id' => $i % 2 === 0 ? $kelompokB->id : $kelompokA->id,
                    'desa_id' => $i % 2 === 0 ? $desaB->id : $desaA->id,
                    'regu_id' => $regu->id,
                    'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
                ]
            );
        }

        for ($i = 6; $i <= 10; $i++) {
            $index = $i - 6;
            $regu = $femaleRegus[$index % count($femaleRegus)];

            peserta::updateOrCreate(
                ['nip' => 900000 + $i],
                [
                    'nama' => 'Peserta Perempuan '.($i - 5),
                    'nip' => 900000 + $i,
                    'jenis_kelamin' => 'Perempuan',
                    'kelompok_id' => $i % 2 === 0 ? $kelompokB->id : $kelompokA->id,
                    'desa_id' => $i % 2 === 0 ? $desaB->id : $desaA->id,
                    'regu_id' => $regu->id,
                    'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
                ]
            );
        }
    }
}
