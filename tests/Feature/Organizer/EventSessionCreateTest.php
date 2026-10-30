<?php

namespace Tests\Feature\Organizer;

use App\Enums\EventSessionStatus;
use App\Enums\OrganizerRole;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventSessionCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 固定「現在」= 台北 2026-10-30 14:00、洛杉磯 2026-10-29 23:00（夏令時間 UTC-7）
        $this->travelTo('2026-10-30 06:00:00');
    }

    public function test_admin_can_view_create_form(): void
    {
        [$admin, $organizer, $event] = $this->eventWithMember(OrganizerRole::Admin);

        $this->actingAs($admin)
            ->get(route('organizers.events.sessions.create', [$organizer, $event]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('organizer/events/sessions/Create')
                ->where('event.id', $event->id)
            );
    }

    public function test_scanner_gets_403_on_create_form(): void
    {
        [$scanner, $organizer, $event] = $this->eventWithMember(OrganizerRole::Scanner);

        $this->actingAs($scanner)
            ->get(route('organizers.events.sessions.create', [$organizer, $event]))
            ->assertForbidden();
    }

    public function test_scanner_cannot_store_session(): void
    {
        [$scanner, $organizer, $event] = $this->eventWithMember(OrganizerRole::Scanner);

        $this->actingAs($scanner)
            ->post(route('organizers.events.sessions.store', [$organizer, $event]), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('event_sessions', 0);
    }

    public function test_owner_gets_403_on_create_form_of_cancelled_event(): void
    {
        [$owner, $organizer, $event] = $this->eventWithMember(OrganizerRole::Owner, cancelled: true);

        $this->actingAs($owner)
            ->get(route('organizers.events.sessions.create', [$organizer, $event]))
            ->assertForbidden();
    }

    public function test_owner_cannot_add_session_to_cancelled_event(): void
    {
        [$owner, $organizer, $event] = $this->eventWithMember(OrganizerRole::Owner, cancelled: true);

        $this->actingAs($owner)
            ->post(route('organizers.events.sessions.store', [$organizer, $event]), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('event_sessions', 0);
    }

    public function test_store_converts_local_times_to_utc_and_syncs_event(): void
    {
        [$admin, $organizer, $event] = $this->eventWithMember(OrganizerRole::Admin);
        $otherEvent = Event::factory()->create();

        $this->actingAs($admin)
            ->post(route('organizers.events.sessions.store', [$organizer, $event]), $this->validPayload([
                'timezone' => 'Asia/Taipei',
                'doors_open_at' => '2026-12-25T18:30',
                'starts_at' => '2026-12-25T19:30',
                'ends_at' => '2026-12-25T22:30',
                // 偷塞的欄位要被忽略
                'event_id' => $otherEvent->id,
                'status' => EventSessionStatus::Cancelled->value,
            ]))
            ->assertRedirect(route('organizers.events.show', [$organizer, $event]));

        $session = EventSession::sole();
        $this->assertSame($event->id, $session->event_id);
        $this->assertSame(EventSessionStatus::Scheduled, $session->status);
        $this->assertSame('Asia/Taipei', $session->timezone);
        // 台北 UTC+8
        $this->assertSame('2026-12-25 10:30:00', $session->doors_open_at?->toDateTimeString());
        $this->assertSame('2026-12-25 11:30:00', $session->starts_at->toDateTimeString());
        $this->assertSame('2026-12-25 14:30:00', $session->ends_at->toDateTimeString());
        $this->assertSame('2026-12-25 14:30:00', $event->fresh()->last_session_ends_at?->toDateTimeString());
    }

    public function test_rejects_start_time_already_past_in_session_timezone(): void
    {
        [$admin, $organizer, $event] = $this->eventWithMember(OrganizerRole::Admin);

        // 台北 13:00 已過去；若誤當成 UTC 13:00 會被當成未來而放行
        $this->actingAs($admin)
            ->post(route('organizers.events.sessions.store', [$organizer, $event]), $this->validPayload([
                'timezone' => 'Asia/Taipei',
                'starts_at' => '2026-10-30T13:00',
                'ends_at' => '2026-10-30T16:00',
            ]))
            ->assertSessionHasErrors(['starts_at' => '開始時間必須晚於現在']);

        $this->assertDatabaseCount('event_sessions', 0);
    }

    public function test_accepts_start_time_still_future_in_session_timezone(): void
    {
        [$admin, $organizer, $event] = $this->eventWithMember(OrganizerRole::Admin);

        // 洛杉磯 02:00 = UTC 09:00，尚未到；若誤當成 UTC 02:00 會被當成過去而擋下
        $this->actingAs($admin)
            ->post(route('organizers.events.sessions.store', [$organizer, $event]), $this->validPayload([
                'timezone' => 'America/Los_Angeles',
                'starts_at' => '2026-10-30T02:00',
                'ends_at' => '2026-10-30T05:00',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-10-30 09:00:00', EventSession::sole()->starts_at->toDateTimeString());
    }

    public function test_accepts_backward_compatible_timezone_name(): void
    {
        [$admin, $organizer, $event] = $this->eventWithMember(OrganizerRole::Admin);

        // Chrome 的 Intl 清單會給舊名稱 Asia/Calcutta（現名 Asia/Kolkata）
        $this->actingAs($admin)
            ->post(route('organizers.events.sessions.store', [$organizer, $event]), $this->validPayload([
                'timezone' => 'Asia/Calcutta',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Asia/Calcutta', EventSession::sole()->timezone);
    }

    public function test_store_requires_venue_timezone_and_times(): void
    {
        [$admin, $organizer, $event] = $this->eventWithMember(OrganizerRole::Admin);

        $this->actingAs($admin)
            ->post(route('organizers.events.sessions.store', [$organizer, $event]), [])
            ->assertSessionHasErrors([
                'venue_name' => 'The venue name field is required.',
                'timezone' => 'The timezone field is required.',
                'starts_at' => 'The starts at field is required.',
                'ends_at' => 'The ends at field is required.',
            ]);
    }

    public function test_rejects_invalid_timezone(): void
    {
        [$admin, $organizer, $event] = $this->eventWithMember(OrganizerRole::Admin);

        $this->actingAs($admin)
            ->post(route('organizers.events.sessions.store', [$organizer, $event]), $this->validPayload([
                'timezone' => 'Mars/Olympus',
            ]))
            ->assertSessionHasErrors(['timezone' => 'The timezone field must be a valid timezone.']);
    }

    public function test_malformed_start_time_returns_validation_error_instead_of_crashing(): void
    {
        [$admin, $organizer, $event] = $this->eventWithMember(OrganizerRole::Admin);

        // after() 必須在格式錯誤時跳過時區轉換，否則會拋例外變 500
        $this->actingAs($admin)
            ->post(route('organizers.events.sessions.store', [$organizer, $event]), $this->validPayload([
                'starts_at' => 'not-a-date',
            ]))
            ->assertSessionHasErrors(['starts_at' => 'The starts at field must match the format Y-m-d\TH:i.']);
    }

    public function test_rejects_end_time_not_after_start_time(): void
    {
        [$admin, $organizer, $event] = $this->eventWithMember(OrganizerRole::Admin);

        $this->actingAs($admin)
            ->post(route('organizers.events.sessions.store', [$organizer, $event]), $this->validPayload([
                'starts_at' => '2026-12-25T19:30',
                'ends_at' => '2026-12-25T19:30',
            ]))
            ->assertSessionHasErrors(['ends_at' => 'The ends at field must be a date after starts at.']);
    }

    public function test_rejects_doors_open_after_start_time(): void
    {
        [$admin, $organizer, $event] = $this->eventWithMember(OrganizerRole::Admin);

        $this->actingAs($admin)
            ->post(route('organizers.events.sessions.store', [$organizer, $event]), $this->validPayload([
                'doors_open_at' => '2026-12-25T20:00',
                'starts_at' => '2026-12-25T19:30',
            ]))
            ->assertSessionHasErrors(['doors_open_at' => 'The doors open at field must be a date before or equal to starts at.']);
    }

    /**
     * @return array{User, Organizer, Event}
     */
    private function eventWithMember(OrganizerRole $role, bool $cancelled = false): array
    {
        $user = User::factory()->create();
        $organizer = Organizer::factory()->withMember($user, $role)->create();
        $factory = Event::factory()->for($organizer);
        $event = ($cancelled ? $factory->cancelled() : $factory)->create();

        return [$user, $organizer, $event];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'venue_name' => '台北小巨蛋',
            'timezone' => 'Asia/Taipei',
            'starts_at' => '2026-12-25T19:30',
            'ends_at' => '2026-12-25T22:30',
            ...$overrides,
        ];
    }
}
