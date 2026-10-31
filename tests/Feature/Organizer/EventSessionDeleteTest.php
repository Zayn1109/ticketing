<?php

namespace Tests\Feature\Organizer;

use App\Enums\OrganizerRole;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Organizer;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventSessionDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_session_of_draft_event_with_its_ticket_types(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin, [
            'starts_at' => '2026-12-31 11:00:00',
            'ends_at' => '2026-12-31 14:00:00',
        ]);
        TicketType::factory()->count(2)->for($session)->create();

        $otherSession = EventSession::factory()->for($event)->create([
            'starts_at' => '2026-12-20 11:00:00',
            'ends_at' => '2026-12-20 14:00:00',
        ]);
        $otherTicketType = TicketType::factory()->for($otherSession)->create();
        $event->syncLastSessionEndsAt();

        $this->actingAs($admin)
            ->delete(route('organizers.events.sessions.destroy', [$organizer, $event, $session]))
            ->assertRedirect(route('organizers.events.show', [$organizer, $event]));

        $this->assertModelMissing($session);
        $this->assertDatabaseMissing('ticket_types', ['event_session_id' => $session->id]);
        // 只刪自己的票種，其他場次的不受影響
        $this->assertModelExists($otherTicketType);
        // 刪掉最晚的場次，last_session_ends_at 要退回另一場
        $this->assertSame('2026-12-20 14:00:00', $event->fresh()?->last_session_ends_at?->toDateTimeString());
    }

    public function test_deleting_only_session_clears_last_session_ends_at(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin);
        $event->syncLastSessionEndsAt();

        $this->actingAs($admin)
            ->delete(route('organizers.events.sessions.destroy', [$organizer, $event, $session]))
            ->assertRedirect();

        $this->assertNull($event->fresh()?->last_session_ends_at);
    }

    public function test_owner_cannot_delete_session_of_published_event(): void
    {
        [$owner, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Owner, published: true);

        $this->actingAs($owner)
            ->delete(route('organizers.events.sessions.destroy', [$organizer, $event, $session]))
            ->assertForbidden();

        $this->assertModelExists($session);
    }

    public function test_scanner_cannot_delete_session(): void
    {
        [$scanner, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Scanner);

        $this->actingAs($scanner)
            ->delete(route('organizers.events.sessions.destroy', [$organizer, $event, $session]))
            ->assertForbidden();

        $this->assertModelExists($session);
    }

    public function test_session_of_another_event_returns_404(): void
    {
        [$admin, $organizer, $event] = $this->sessionWithMember(OrganizerRole::Admin);
        // 同一個主辦方、另一個草稿活動的場次
        $otherSession = EventSession::factory()
            ->for(Event::factory()->for($organizer))
            ->create();

        $this->actingAs($admin)
            ->delete(route('organizers.events.sessions.destroy', [$organizer, $event, $otherSession]))
            ->assertNotFound();

        $this->assertModelExists($otherSession);
    }

    /**
     * @param  array<string, mixed>  $sessionAttributes
     * @return array{User, Organizer, Event, EventSession}
     */
    private function sessionWithMember(
        OrganizerRole $role,
        array $sessionAttributes = [],
        bool $published = false,
    ): array {
        $user = User::factory()->create();
        $organizer = Organizer::factory()->withMember($user, $role)->create();
        $eventFactory = Event::factory()->for($organizer);
        $event = ($published ? $eventFactory->published() : $eventFactory)->create();
        $session = EventSession::factory()->for($event)->create($sessionAttributes);

        return [$user, $organizer, $event, $session];
    }
}
