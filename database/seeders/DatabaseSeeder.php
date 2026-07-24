<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LegacyEventSeeder::class,
            EventSeeder::class,
            DesaSeeder::class,
            KelompokSeeder::class,
            // PersonSeeder::class,
            UserSeeder::class,
        ]);
    }
}
