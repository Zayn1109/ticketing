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

class TicketTypeDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_ticket_type_of_draft_event(): void
    {
        [$admin, $organizer, $event, $session, $ticketType] = $this->ticketTypeWithMember(OrganizerRole::Admin);
        $siblingTicketType = TicketType::factory()->for($session)->create();

        $this->actingAs($admin)
            ->delete($this->destroyUrl($organizer, $event, $session, $ticketType))
            ->assertRedirect(route('organizers.events.show', [$organizer, $event]));

        $this->assertModelMissing($ticketType);
        // 只刪指定的票種，同場次的其他票種不受影響
        $this->assertModelExists($siblingTicketType);
        $this->assertModelExists($session);
    }

    public function test_owner_cannot_delete_ticket_type_of_published_event(): void
    {
        [$owner, $organizer, $event, $session, $ticketType] = $this->ticketTypeWithMember(OrganizerRole::Owner, published: true);

        $this->actingAs($owner)
            ->delete($this->destroyUrl($organizer, $event, $session, $ticketType))
            ->assertForbidden();

        $this->assertModelExists($ticketType);
    }

    public function test_scanner_cannot_delete_ticket_type(): void
    {
        [$scanner, $organizer, $event, $session, $ticketType] = $this->ticketTypeWithMember(OrganizerRole::Scanner);

        $this->actingAs($scanner)
            ->delete($this->destroyUrl($organizer, $event, $session, $ticketType))
            ->assertForbidden();

        $this->assertModelExists($ticketType);
    }

    public function test_ticket_type_of_another_session_returns_404(): void
    {
        [$admin, $organizer, $event, $session] = $this->ticketTypeWithMember(OrganizerRole::Admin);
        // 同一個活動、另一個場次的票種
        $otherTicketType = TicketType::factory()
            ->for(EventSession::factory()->for($event))
            ->create();

        $this->actingAs($admin)
            ->delete($this->destroyUrl($organizer, $event, $session, $otherTicketType))
            ->assertNotFound();

        $this->assertModelExists($otherTicketType);
    }

    /**
     * @return array{User, Organizer, Event, EventSession, TicketType}
     */
    private function ticketTypeWithMember(OrganizerRole $role, bool $published = false): array
    {
        $user = User::factory()->create();
        $organizer = Organizer::factory()->withMember($user, $role)->create();
        $eventFactory = Event::factory()->for($organizer);
        $event = ($published ? $eventFactory->published() : $eventFactory)->create();
        $session = EventSession::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($session)->create();

        return [$user, $organizer, $event, $session, $ticketType];
    }

    private function destroyUrl(Organizer $organizer, Event $event, EventSession $session, TicketType $ticketType): string
    {
        return route('organizers.events.sessions.ticket-types.destroy', [$organizer, $event, $session, $ticketType]);
    }
}
