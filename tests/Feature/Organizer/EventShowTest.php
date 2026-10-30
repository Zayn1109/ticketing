<?php

namespace Tests\Feature\Organizer;

use App\Enums\OrganizerRole;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Organizer;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_includes_sessions_and_ticket_types(): void
    {
        $user = User::factory()->create();
        $organizer = Organizer::factory()->withMember($user, OrganizerRole::Scanner)->create();
        $event = Event::factory()->for($organizer)->create();

        // 故意讓晚的場次先建立，驗證畫面上是依開始時間排序
        $later = EventSession::factory()->for($event)->create(['starts_at' => now()->addDays(10)]);
        $earlier = EventSession::factory()->for($event)->create(['starts_at' => now()->addDays(5)]);

        TicketType::factory()->count(2)->for($earlier)->create();

        $this->actingAs($user)
            ->get(route('organizers.events.show', [$organizer, $event]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('organizer/events/Show')
                ->where('organizer.id', $organizer->id)
                ->has('event.sessions', 2)
                ->where('event.sessions.0.id', $earlier->id)
                ->where('event.sessions.1.id', $later->id)
                ->has('event.sessions.0.ticket_types', 2)
                ->missing('event.created_by')
                ->missing('event.cancelled_by')
            );
    }

    public function test_show_allows_admin_to_update_editable_event(): void
    {
        $admin = User::factory()->create();
        $organizer = Organizer::factory()->withMember($admin, OrganizerRole::Admin)->create();
        $event = Event::factory()->for($organizer)->create();

        $this->actingAs($admin)
            ->get(route('organizers.events.show', [$organizer, $event]))
            ->assertInertia(fn (Assert $page) => $page->where('can.update', true));
    }

    public function test_show_denies_update_to_scanner(): void
    {
        $scanner = User::factory()->create();
        $organizer = Organizer::factory()->withMember($scanner, OrganizerRole::Scanner)->create();
        $event = Event::factory()->for($organizer)->create();

        $this->actingAs($scanner)
            ->get(route('organizers.events.show', [$organizer, $event]))
            ->assertInertia(fn (Assert $page) => $page->where('can.update', false));
    }

    public function test_show_denies_update_of_cancelled_event_even_to_owner(): void
    {
        $owner = User::factory()->create();
        $organizer = Organizer::factory()->withMember($owner, OrganizerRole::Owner)->create();
        $event = Event::factory()->for($organizer)->cancelled()->create();

        $this->actingAs($owner)
            ->get(route('organizers.events.show', [$organizer, $event]))
            ->assertInertia(fn (Assert $page) => $page->where('can.update', false));
    }
}
