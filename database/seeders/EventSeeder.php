<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Support\ActiveEventContext;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::updateOrCreate(
            ['slug' => 'pengajian'],
            [
                'name' => 'Pengajian',
                'event_type' => 'pengajian',
                'description' => null,
                'start_date' => null,
                'end_date' => null,
                'status' => 'active',
            ],
        );

        app(ActiveEventContext::class)->set($event);
    }
}
