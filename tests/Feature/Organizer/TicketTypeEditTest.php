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

class TicketTypeEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 固定「現在」= 台北 2026-10-30 14:00
        $this->travelTo('2026-10-30 06:00:00');
    }

    public function test_edit_form_shows_sale_times_in_session_timezone(): void
    {
        [$admin, $organizer, $event, $session, $ticketType] = $this->ticketTypeWithMember(OrganizerRole::Admin);

        $this->actingAs($admin)
            ->get($this->editUrl($organizer, $event, $session, $ticketType))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('organizer/events/ticket-types/Edit')
                ->where('ticketType.id', $ticketType->id)
                // 預設販售期間 UTC 2026-11-01 04:00 – 2026-12-25 11:00 → 台北 +8
                ->where('ticketType.sale_start_at', '2026-11-01T12:00')
                ->where('ticketType.sale_end_at', '2026-12-25T19:00')
            );
    }

    public function test_scanner_cannot_update_ticket_type(): void
    {
        [$scanner, $organizer, $event, $session, $ticketType] = $this->ticketTypeWithMember(OrganizerRole::Scanner);

        $this->actingAs($scanner)
            ->put($this->updateUrl($organizer, $event, $session, $ticketType), $this->validPayload())
            ->assertForbidden();

        $this->assertSame('早鳥票', $ticketType->fresh()?->name);
    }

    public function test_owner_gets_403_on_edit_form_of_cancelled_event(): void
    {
        [$owner, $organizer, $event, $session, $ticketType] = $this->ticketTypeWithMember(OrganizerRole::Owner, eventCancelled: true);

        $this->actingAs($owner)
            ->get($this->editUrl($organizer, $event, $session, $ticketType))
            ->assertForbidden();
    }

    public function test_owner_cannot_update_ticket_type_of_cancelled_session(): void
    {
        [$owner, $organizer, $event, $session, $ticketType] = $this->ticketTypeWithMember(OrganizerRole::Owner, sessionCancelled: true);

        $this->actingAs($owner)
            ->put($this->updateUrl($organizer, $event, $session, $ticketType), $this->validPayload())
            ->assertForbidden();

        $this->assertSame('早鳥票', $ticketType->fresh()?->name);
    }

    public function test_ticket_type_of_another_session_returns_404(): void
    {
        [$admin, $organizer, $event, $session] = $this->ticketTypeWithMember(OrganizerRole::Admin);
        // 同一個活動、另一個場次的票種
        $otherTicketType = TicketType::factory()
            ->for(EventSession::factory()->for($event))
            ->create();

        $this->actingAs($admin)
            ->get($this->editUrl($organizer, $event, $session, $otherTicketType))
            ->assertNotFound();
    }

    public function test_update_converts_sale_times_and_keeps_session(): void
    {
        [$admin, $organizer, $event, $session, $ticketType] = $this->ticketTypeWithMember(OrganizerRole::Admin);
        $otherSession = EventSession::factory()->create();

        $this->actingAs($admin)
            ->put($this->updateUrl($organizer, $event, $session, $ticketType), $this->validPayload([
                'sale_start_at' => '2026-11-15T10:00',
                'sale_end_at' => '2026-12-20T23:59',
                // 偷塞的欄位要被忽略
                'event_session_id' => $otherSession->id,
            ]))
            ->assertRedirect(route('organizers.events.show', [$organizer, $event]));

        $ticketType->refresh();
        $this->assertSame($session->id, $ticketType->event_session_id);
        $this->assertSame('全票', $ticketType->name);
        $this->assertSame(2200, $ticketType->price);
        $this->assertSame(300, $ticketType->quota);
        $this->assertNull($ticketType->max_per_user);
        $this->assertSame('2026-11-15 02:00:00', $ticketType->sale_start_at->toDateTimeString());
        $this->assertSame('2026-12-20 15:59:00', $ticketType->sale_end_at->toDateTimeString());
    }

    public function test_can_rename_ticket_type_whose_sale_already_ended(): void
    {
        // 已停售：結束販售 = 台北 2026-10-29 20:00:45（DB 有秒數，表單只到分鐘）
        [$admin, $organizer, $event, $session, $ticketType] = $this->ticketTypeWithMember(OrganizerRole::Admin, [
            'sale_start_at' => '2026-10-01 04:00:00',
            'sale_end_at' => '2026-10-29 12:00:45',
        ]);

        $this->actingAs($admin)
            ->put($this->updateUrl($organizer, $event, $session, $ticketType), $this->validPayload([
                'sale_start_at' => '2026-10-01T12:00',
                'sale_end_at' => '2026-10-29T20:00',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('全票', $ticketType->fresh()?->name);
    }

    public function test_rejects_changing_sale_end_to_past(): void
    {
        [$admin, $organizer, $event, $session, $ticketType] = $this->ticketTypeWithMember(OrganizerRole::Admin);

        // 台北 13:00 已過去
        $this->actingAs($admin)
            ->put($this->updateUrl($organizer, $event, $session, $ticketType), $this->validPayload([
                'sale_start_at' => '2026-10-30T12:00',
                'sale_end_at' => '2026-10-30T13:00',
            ]))
            ->assertSessionHasErrors(['sale_end_at' => '結束販售時間必須晚於現在']);
    }

    public function test_rejects_sale_end_after_session_ends(): void
    {
        [$admin, $organizer, $event, $session, $ticketType] = $this->ticketTypeWithMember(OrganizerRole::Admin);

        // 場次在台北 22:30 結束
        $this->actingAs($admin)
            ->put($this->updateUrl($organizer, $event, $session, $ticketType), $this->validPayload([
                'sale_end_at' => '2026-12-25T22:31',
            ]))
            ->assertSessionHasErrors(['sale_end_at' => '結束販售時間不可晚於場次結束時間']);
    }

    public function test_update_requires_fields(): void
    {
        [$admin, $organizer, $event, $session, $ticketType] = $this->ticketTypeWithMember(OrganizerRole::Admin);

        $this->actingAs($admin)
            ->put($this->updateUrl($organizer, $event, $session, $ticketType), [])
            ->assertSessionHasErrors([
                'name' => 'The name field is required.',
                'sale_end_at' => 'The sale end at field is required.',
            ]);
    }

    /**
     * 預設場次：台北 2026-12-25 19:30–22:30，容量 500；
     * 預設票種：早鳥票，販售期間台北 2026-11-01 12:00 – 2026-12-25 19:00
     *
     * @param  array<string, mixed>  $ticketTypeAttributes
     * @return array{User, Organizer, Event, EventSession, TicketType}
     */
    private function ticketTypeWithMember(
        OrganizerRole $role,
        array $ticketTypeAttributes = [],
        bool $eventCancelled = false,
        bool $sessionCancelled = false,
    ): array {
        $user = User::factory()->create();
        $organizer = Organizer::factory()->withMember($user, $role)->create();
        $eventFactory = Event::factory()->for($organizer);
        $event = ($eventCancelled ? $eventFactory->cancelled() : $eventFactory)->create();
        $sessionFactory = EventSession::factory()->for($event);
        $session = ($sessionCancelled ? $sessionFactory->cancelled() : $sessionFactory)->create([
            'timezone' => 'Asia/Taipei',
            'starts_at' => '2026-12-25 11:30:00',
            'ends_at' => '2026-12-25 14:30:00',
            'capacity' => 500,
        ]);
        $ticketType = TicketType::factory()->for($session)->create([
            'name' => '早鳥票',
            'sale_start_at' => '2026-11-01 04:00:00',
            'sale_end_at' => '2026-12-25 11:00:00',
            ...$ticketTypeAttributes,
        ]);

        return [$user, $organizer, $event, $session, $ticketType];
    }

    private function editUrl(Organizer $organizer, Event $event, EventSession $session, TicketType $ticketType): string
    {
        return route('organizers.events.sessions.ticket-types.edit', [$organizer, $event, $session, $ticketType]);
    }

    private function updateUrl(Organizer $organizer, Event $event, EventSession $session, TicketType $ticketType): string
    {
        return route('organizers.events.sessions.ticket-types.update', [$organizer, $event, $session, $ticketType]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => '全票',
            'price' => 2200,
            'quota' => 300,
            'sale_start_at' => '2026-11-01T12:00',
            'sale_end_at' => '2026-12-25T19:00',
            'max_per_user' => null,
            ...$overrides,
        ];
    }
}
