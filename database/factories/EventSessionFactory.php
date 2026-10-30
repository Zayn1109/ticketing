<?php

namespace Database\Factories;

use App\Enums\EventSessionStatus;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
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

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => EventSessionStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => User::factory(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }
}
