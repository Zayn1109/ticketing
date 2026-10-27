<?php

namespace Database\Factories;

use App\Enums\OrganizerRole;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organizer>
 */
class OrganizerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'tax_id' => fake()->unique()->numerify('########'),
            'contact_email' => fake()->companyEmail(),
            'contact_phone' => fake()->phoneNumber(),
        ];
    }

    /**
     * 建立後把指定使用者加為成員
     */
    public function withMember(User $user, OrganizerRole $role): static
    {
        return $this->afterCreating(function (Organizer $organizer) use ($user, $role) {
            $organizer->members()->attach($user, ['role' => $role, 'joined_at' => now()]);
        });
    }
}
