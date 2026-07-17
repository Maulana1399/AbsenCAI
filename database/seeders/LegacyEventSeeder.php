<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class LegacyEventSeeder extends Seeder
{
    public function run(): void
    {
        $name = config('kjam.mvp_name', 'CAI Operational');

        Event::firstOrCreate(
            ['slug' => 'cai-operational'],
            [
                'name' => $name,
                'description' => 'Legacy CAI Operational event. Auto-created during migration to Multi Event architecture.',
                'start_date' => null,
                'end_date' => null,
                'status' => 'active',
            ]
        );
    }
}
