<?php

namespace Tests\Feature\Organizer;

use App\Enums\OrganizerRole;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SharedOrganizersTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_sees_own_organizers_with_role(): void
    {
        $user = User::factory()->create();
        $first = Organizer::factory()->withMember($user, OrganizerRole::Admin)->create();
        $second = Organizer::factory()->withMember($user, OrganizerRole::Scanner)->create();

        // 不是成員的主辦方，不應該出現
        Organizer::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('organizers', 2)
                ->where('organizers.0', ['id' => $first->id, 'name' => $first->name, 'role' => 'admin'])
                ->where('organizers.1', ['id' => $second->id, 'name' => $second->name, 'role' => 'scanner'])
            );
    }

    public function test_guest_gets_empty_organizers(): void
    {
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('organizers', [])
            );
    }
}
