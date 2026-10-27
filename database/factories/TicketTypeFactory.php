<?php

namespace Database\Factories;

use App\Models\EventSession;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_session_id' => EventSession::factory(),
            'name' => fake()->randomElement(['早鳥票', '全票', '學生票', 'VIP']),
            'price' => fake()->numberBetween(5, 50) * 100,
            'quota' => 100,
            'sale_start_at' => now(),
            'sale_end_at' => now()->addWeeks(2),
            'max_per_user' => 4,
        ];
    }
}
