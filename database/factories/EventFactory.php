<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $name = fake()->unique()->company().' Event';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'event_type' => 'competition',
            'description' => fake()->sentence(),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'status' => 'active',
        ];
    }
}
