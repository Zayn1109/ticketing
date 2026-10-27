<?php

namespace Database\Factories;

use App\Enums\EventSessionStatus;
use App\Models\Event;
use App\Models\EventSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<EventSession>
 */
class EventSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = Carbon::instance(fake()->dateTimeBetween('+1 week', '+3 months'));

        return [
            'event_id' => Event::factory(),
            'venue_name' => fake()->city().'體育館',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(3),
            'timezone' => 'Asia/Taipei',
            'capacity' => 1000,
            'status' => EventSessionStatus::Scheduled,
        ];
    }
}
