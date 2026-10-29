<?php

namespace Tests\Feature\Organizer;

use App\Enums\OrganizerRole;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_this_organizers_events_newest_first(): void
    {
        $user = User::factory()->create();
        $organizer = Organizer::factory()->withMember($user, OrganizerRole::Scanner)->create();

        $older = Event::factory()->for($organizer)->create(['created_at' => now()->subDay()]);
        $newer = Event::factory()
            ->for($organizer)
            ->has(EventSession::factory()->count(2), 'sessions')
            ->create();

        // 別的主辦方的活動，不應該出現在列表裡
        Event::factory()->create();

        $this->actingAs($user)
            ->get(route('organizers.events.index', $organizer))
            ->assertInertia(fn (Assert $page) => $page
                ->component('organizer/events/Index')
                ->where('organizer.id', $organizer->id)
                ->has('events', 2)
                ->where('events.0.id', $newer->id)
                ->where('events.0.sessions_count', 2)
                ->where('events.1.id', $older->id)
                ->where('events.1.sessions_count', 0)
                ->missing('events.0.created_by')
                ->missing('events.0.description')
            );
    }
}
