<?php

namespace Database\Seeders;

use App\Models\kelompok;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PersonSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'KM 7' => [
                'L' => ['Ahmad Fauzan', 'Rizky Ramadhan'],
                'P' => ['Siti Aisyah', 'Putri Maharani'],
            ],
            'KM 10' => [
                'L' => ['Muhammad Fajar', 'Dimas Saputra'],
                'P' => ['Nur Azizah', 'Dinda Safitri'],
            ],
            'Perumnas' => [
                'L' => ['Andi Pratama', 'Arif Hidayat'],
                'P' => ['Rina Oktavia', 'Ayu Lestari'],
            ],
            'Kariangau' => [
                'L' => ['Bagas Nugroho', 'Fajar Ramdani'],
                'P' => ['Rahmawati', 'Indah Permata'],
            ],
            'Soekarno Hatta' => [
                'L' => ['Gilang Pramudya', 'Hendra Gunawan'],
                'P' => ['Dewi Sartika', 'Fitri Handayani'],
            ],
            'Somber' => [
                'L' => ['Indra Lesmana', 'Joko Susilo'],
                'P' => ['Gina Nurmalasari', 'Hesti Purnama'],
            ],
            'Gunung Samarinda' => [
                'L' => ['Kevin Maulana', 'Lukman Hakim'],
                'P' => ['Intan Permata Sari', 'Julia Rahmawati'],
            ],
            'Sumber Rejo' => [
                'L' => ['Mochamad Rizal', 'Noval Ardiansyah'],
                'P' => ['Karina Wulandari', 'Lestari Dewi'],
            ],
            'Bandara Utara' => [
                'L' => ['Oki Setiawan', 'Pramudya Ananda'],
                'P' => ['Mutiara Sari', 'Nabila Putri'],
            ],
            'Sepinggan 1' => [
                'L' => ['Qori Firmansyah', 'Rafi Akbar'],
                'P' => ['Olivia Anggraini', 'Pratiwi Kusuma'],
            ],
            'Sepinggan 2' => [
                'L' => ['Sandy Wicaksono', 'Taufik Hidayat'],
                'P' => ['Ranti Fauziah', 'Septi Aulia'],
            ],
            'Sepinggan 3' => [
                'L' => ['Ujang Hermawan', 'Vicky Pratama'],
                'P' => ['Tri Wahyuni', 'Umi Kalsum'],
            ],
            'Sepinggan 4' => [
                'L' => ['Wahyu Nugroho', 'Yoga Permana'],
                'P' => ['Vina Melinda', 'Winda Sari'],
            ],
            'Bandara Baru' => [
                'L' => ['Zainal Arifin', 'Aditya Saputra'],
                'P' => ['Yuniarti', 'Zaskia Ramadhani'],
            ],
            'Melati' => [
                'L' => ['Budi Santoso', 'Chandra Wijaya'],
                'P' => ['Aulia Rahman', 'Bella Safira'],
            ],
            'Lemaru' => [
                'L' => ['Denny Kurniawan', 'Eko Prasetyo'],
                'P' => ['Cinta Devi', 'Dwi Lestari'],
            ],
            'Batakan' => [
                'L' => ['Farhan Ramadhan', 'Gunawan Saputra'],
                'P' => ['Eka Cahyani', 'Fadila Nur'],
            ],
            'Lemaru Sosial' => [
                'L' => ['Habibie Rahman', 'Irfan Maulana'],
                'P' => ['Gita Permata', 'Hana Safitri'],
            ],
            'Manggar' => [
                'L' => ['Jefri Ardiansyah', 'Kurniawan Putra'],
                'P' => ['Irma Susanti', 'Jasmine Aliyah'],
            ],
        ];

        $birthSeed = 0;

        foreach ($data as $kelompokName => $genders) {
            $kelompok = kelompok::where('kelompok_asal', $kelompokName)->first();

            if ($kelompok === null) {
                continue;
            }

            foreach ($genders as $gender => $names) {
                foreach ($names as $name) {
                    $birthSeed++;

                    $birthDate = Carbon::create(
                        1990 + ($birthSeed % 18),
                        1 + (($birthSeed * 7) % 12),
                        1 + (($birthSeed * 13) % 28),
                    );

                    Person::updateOrCreate(
                        ['nama' => $name],
                        [
                            'jenis_kelamin' => $gender,
                            'desa_id' => $kelompok->desa_id,
                            'kelompok_id' => $kelompok->id,
                            'tanggal_lahir' => $birthDate,
                        ],
                    );
                }
            }
        }
    }
}
