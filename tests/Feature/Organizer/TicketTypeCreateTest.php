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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketTypeCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 固定「現在」= 台北 2026-10-30 14:00
        $this->travelTo('2026-10-30 06:00:00');
    }

    public function test_admin_can_view_create_form(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin);

        $this->actingAs($admin)
            ->get(route('organizers.events.sessions.ticket-types.create', [$organizer, $event, $session]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('organizer/events/ticket-types/Create')
                ->where('session.id', $session->id)
                ->where('session.timezone', 'Asia/Taipei')
                ->where('session.capacity', 500)
            );
    }

    public function test_scanner_gets_403_on_create_form(): void
    {
        [$scanner, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Scanner);

        $this->actingAs($scanner)
            ->get(route('organizers.events.sessions.ticket-types.create', [$organizer, $event, $session]))
            ->assertForbidden();
    }

    public function test_scanner_cannot_store_ticket_type(): void
    {
        [$scanner, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Scanner);

        $this->actingAs($scanner)
            ->post($this->storeUrl($organizer, $event, $session), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('ticket_types', 0);
    }

    public function test_owner_gets_403_on_create_form_of_cancelled_event(): void
    {
        [$owner, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Owner, eventCancelled: true);

        $this->actingAs($owner)
            ->get(route('organizers.events.sessions.ticket-types.create', [$organizer, $event, $session]))
            ->assertForbidden();
    }

    public function test_owner_cannot_add_ticket_type_to_cancelled_session(): void
    {
        [$owner, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Owner, sessionCancelled: true);

        $this->actingAs($owner)
            ->post($this->storeUrl($organizer, $event, $session), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('ticket_types', 0);
    }

    public function test_session_of_another_event_returns_404(): void
    {
        [$admin, $organizer, $event] = $this->sessionWithMember(OrganizerRole::Admin);
        // 同一個主辦方、另一個活動的場次
        $otherSession = EventSession::factory()->for(Event::factory()->for($organizer))->create();

        $this->actingAs($admin)
            ->post($this->storeUrl($organizer, $event, $otherSession), $this->validPayload())
            ->assertNotFound();

        $this->assertDatabaseCount('ticket_types', 0);
    }

    public function test_store_converts_sale_times_using_session_timezone(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin, [
            'timezone' => 'America/Los_Angeles',
            // 洛杉磯 2026-12-25 19:30–22:30（冬令 UTC-8）
            'starts_at' => '2026-12-26 03:30:00',
            'ends_at' => '2026-12-26 06:30:00',
        ]);
        $otherSession = EventSession::factory()->create();

        $this->actingAs($admin)
            ->post($this->storeUrl($organizer, $event, $session), $this->validPayload([
                'sale_start_at' => '2026-11-10T10:00',
                'sale_end_at' => '2026-12-25T19:00',
                // 偷塞的欄位要被忽略
                'event_session_id' => $otherSession->id,
            ]))
            ->assertRedirect(route('organizers.events.show', [$organizer, $event]));

        $ticketType = TicketType::sole();
        $this->assertSame($session->id, $ticketType->event_session_id);
        $this->assertSame('全票', $ticketType->name);
        $this->assertSame(1800, $ticketType->price);
        $this->assertSame(200, $ticketType->quota);
        $this->assertSame(4, $ticketType->max_per_user);
        $this->assertSame('2026-11-10 18:00:00', $ticketType->sale_start_at->toDateTimeString());
        $this->assertSame('2026-12-26 03:00:00', $ticketType->sale_end_at->toDateTimeString());
    }

    public function test_accepts_free_ticket_without_purchase_limit(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin);

        $this->actingAs($admin)
            ->post($this->storeUrl($organizer, $event, $session), $this->validPayload([
                'price' => 0,
                'max_per_user' => null,
            ]))
            ->assertSessionHasNoErrors();

        $ticketType = TicketType::sole();
        $this->assertSame(0, $ticketType->price);
        $this->assertNull($ticketType->max_per_user);
    }

    public function test_store_requires_name_price_quota_and_sale_times(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin);

        $this->actingAs($admin)
            ->post($this->storeUrl($organizer, $event, $session), [])
            ->assertSessionHasErrors([
                'name' => 'The name field is required.',
                'price' => 'The price field is required.',
                'quota' => 'The quota field is required.',
                'sale_start_at' => 'The sale start at field is required.',
                'sale_end_at' => 'The sale end at field is required.',
            ]);
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function outOfRangeNumbers(): array
    {
        return [
            '價格不可為負' => ['price', -1, 'The price field must be at least 0.'],
            '價格上限一百萬' => ['price', 1_000_001, 'The price field must not be greater than 1000000.'],
            '配額至少 1' => ['quota', 0, 'The quota field must be at least 1.'],
            '限購至少 1' => ['max_per_user', 0, 'The max per user field must be at least 1.'],
        ];
    }

    #[DataProvider('outOfRangeNumbers')]
    public function test_rejects_out_of_range_numbers(string $field, int $value, string $message): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin);

        $this->actingAs($admin)
            ->post($this->storeUrl($organizer, $event, $session), $this->validPayload([$field => $value]))
            ->assertSessionHasErrors([$field => $message]);
    }

    public function test_rejects_quota_above_session_capacity(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin);

        $this->actingAs($admin)
            ->post($this->storeUrl($organizer, $event, $session), $this->validPayload(['quota' => 501]))
            ->assertSessionHasErrors(['quota' => 'The quota field must not be greater than 500.']);
    }

    public function test_allows_any_quota_when_session_has_no_capacity(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin, ['capacity' => null]);

        $this->actingAs($admin)
            ->post($this->storeUrl($organizer, $event, $session), $this->validPayload(['quota' => 100_000]))
            ->assertSessionHasNoErrors();

        $this->assertSame(100_000, TicketType::sole()->quota);
    }

    public function test_rejects_purchase_limit_above_quota(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin);

        $this->actingAs($admin)
            ->post($this->storeUrl($organizer, $event, $session), $this->validPayload([
                'quota' => 10,
                'max_per_user' => 11,
            ]))
            ->assertSessionHasErrors(['max_per_user' => 'The max per user field must be less than or equal to 10.']);
    }

    public function test_rejects_sale_end_not_after_sale_start(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin);

        $this->actingAs($admin)
            ->post($this->storeUrl($organizer, $event, $session), $this->validPayload([
                'sale_start_at' => '2026-12-01T12:00',
                'sale_end_at' => '2026-12-01T12:00',
            ]))
            ->assertSessionHasErrors(['sale_end_at' => 'The sale end at field must be a date after sale start at.']);
    }

    public function test_rejects_sale_end_after_session_ends(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin);

        // 場次在台北 22:30 結束
        $this->actingAs($admin)
            ->post($this->storeUrl($organizer, $event, $session), $this->validPayload([
                'sale_end_at' => '2026-12-25T22:31',
            ]))
            ->assertSessionHasErrors(['sale_end_at' => '結束販售時間不可晚於場次結束時間']);

        $this->assertDatabaseCount('ticket_types', 0);
    }

    public function test_accepts_sale_end_exactly_at_session_end_in_session_timezone(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin);

        // 台北 22:30 = UTC 14:30；若誤當成 UTC 22:30 會被當成晚於場次結束而擋下
        $this->actingAs($admin)
            ->post($this->storeUrl($organizer, $event, $session), $this->validPayload([
                'sale_end_at' => '2026-12-25T22:30',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-12-25 14:30:00', TicketType::sole()->sale_end_at->toDateTimeString());
    }

    public function test_rejects_sale_end_already_past_in_session_timezone(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin);

        // 台北 13:00 已過去；若誤當成 UTC 13:00 會被當成未來而放行
        $this->actingAs($admin)
            ->post($this->storeUrl($organizer, $event, $session), $this->validPayload([
                'sale_start_at' => '2026-10-30T12:00',
                'sale_end_at' => '2026-10-30T13:00',
            ]))
            ->assertSessionHasErrors(['sale_end_at' => '結束販售時間必須晚於現在']);
    }

    public function test_malformed_sale_end_returns_validation_error_instead_of_crashing(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin);

        // after() 必須在格式錯誤時跳過時區轉換，否則會拋例外變 500
        $this->actingAs($admin)
            ->post($this->storeUrl($organizer, $event, $session), $this->validPayload([
                'sale_end_at' => 'not-a-date',
            ]))
            ->assertSessionHasErrors(['sale_end_at' => 'The sale end at field must match the format Y-m-d\TH:i.']);
    }

    /**
     * 預設場次：台北 2026-12-25 19:30–22:30，容量 500
     *
     * @param  array<string, mixed>  $sessionAttributes
     * @return array{User, Organizer, Event, EventSession}
     */
    private function sessionWithMember(
        OrganizerRole $role,
        array $sessionAttributes = [],
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
            ...$sessionAttributes,
        ]);

        return [$user, $organizer, $event, $session];
    }

    private function storeUrl(Organizer $organizer, Event $event, EventSession $session): string
    {
        return route('organizers.events.sessions.ticket-types.store', [$organizer, $event, $session]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => '全票',
            'price' => 1800,
            'quota' => 200,
            'sale_start_at' => '2026-11-01T12:00',
            'sale_end_at' => '2026-12-25T19:00',
            'max_per_user' => 4,
            ...$overrides,
        ];
    }
}
