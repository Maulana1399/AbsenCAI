<?php

namespace Database\Factories;

use App\Models\desa;
use App\Models\kelompok;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonFactory extends Factory
{
    protected $model = Person::class;

    private static array $firstNameM = [
        'Ahmad', 'Muhammad', 'Rizky', 'Fajar', 'Dimas', 'Andi', 'Budi',
        'Rudi', 'Hendra', 'Agus', 'Doni', 'Eko', 'Firman', 'Gilang',
        'Hafidz', 'Irfan', 'Joko', 'Kurnia', 'Lukman', 'Miftah',
        'Nanda', 'Oki', 'Pratama', 'Rahmat', 'Sandi', 'Taufik',
        'Ujang', 'Wahyu', 'Yudi', 'Zainal', 'Aditya', 'Bagas',
        'Chandra', 'Denny', 'Farhan',
    ];

    private static array $firstNameF = [
        'Siti', 'Nurul', 'Dewi', 'Rina', 'Fitri', 'Lina', 'Maya',
        'Nina', 'Putri', 'Rani', 'Sari', 'Titin', 'Wulan', 'Yuni',
        'Aisyah', 'Bunga', 'Citra', 'Dian', 'Elok', 'Friska',
        'Gita', 'Hana', 'Indah', 'Juwita', 'Kartika', 'Lestari',
        'Mega', 'Nadia', 'Nita', 'Dwi', 'Intan', 'Ratna',
        'Vina', 'Winda', 'Zaskia',
    ];

    private static array $lastNames = [
        'Wijaya', 'Kusuma', 'Pratama', 'Utama', 'Santoso', 'Hidayat',
        'Nugroho', 'Saputra', 'Setiawan', 'Wibowo', 'Yulianto',
        'Rahmawati', 'Hasanah', 'Maryam', 'Fitriani', 'Handayani',
        'Pertiwi', 'Wulandari', 'Amalia', 'Khairunnisa',
        'Susanti', 'Purnama', 'Anggraini', 'Maulana', 'Ramadhan',
        'Ardiansyah', 'Gunawan', 'Prasetyo', 'Kurniawan', 'Hermawan',
    ];

    public function definition(): array
    {
        $gender = fake()->randomElement(['L', 'P']);
        $firstNames = $gender === 'L' ? self::$firstNameM : self::$firstNameF;
        $nama = $firstNames[array_rand($firstNames)] . ' ' . self::$lastNames[array_rand(self::$lastNames)];

        $desa = desa::inRandomOrder()->first();
        $kelompok = $desa ? kelompok::where('desa_id', $desa->id)->inRandomOrder()->first() : null;

        return [
            'nama' => $nama,
            'jenis_kelamin' => $gender,
            'tanggal_lahir' => fake()->date('Y-m-d', '2010-01-01'),
            'desa_id' => $desa?->id,
            'kelompok_id' => $kelompok?->id,
        ];
    }
}
