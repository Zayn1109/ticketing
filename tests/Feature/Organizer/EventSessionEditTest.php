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

class EventSessionEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 固定「現在」= 台北 2026-10-30 14:00
        $this->travelTo('2026-10-30 06:00:00');
    }

    public function test_edit_form_shows_times_in_session_timezone(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin, [
            'name' => '台北場',
            'venue_name' => '台北小巨蛋',
            'venue_address' => null,
            'timezone' => 'Asia/Taipei',
            'starts_at' => '2026-12-25 11:30:00',
            'ends_at' => '2026-12-25 14:30:00',
            'doors_open_at' => null,
            'capacity' => 1000,
        ]);

        $this->actingAs($admin)
            ->get(route('organizers.events.sessions.edit', [$organizer, $event, $session]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('organizer/events/sessions/Edit')
                // 整個物件比對：時間要轉成台北當地時間，且不能多傳 status 等欄位
                ->where('session', [
                    'id' => $session->id,
                    'name' => '台北場',
                    'venue_name' => '台北小巨蛋',
                    'venue_address' => null,
                    'timezone' => 'Asia/Taipei',
                    'starts_at' => '2026-12-25T19:30',
                    'ends_at' => '2026-12-25T22:30',
                    'doors_open_at' => null,
                    'capacity' => 1000,
                ])
            );
    }

    public function test_scanner_cannot_update_session(): void
    {
        [$scanner, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Scanner);

        $this->actingAs($scanner)
            ->put(route('organizers.events.sessions.update', [$organizer, $event, $session]), $this->validPayload())
            ->assertForbidden();

        $this->assertNotSame('新場館', $session->fresh()?->venue_name);
    }

    public function test_owner_cannot_update_cancelled_session(): void
    {
        [$owner, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Owner, sessionCancelled: true);

        $this->actingAs($owner)
            ->put(route('organizers.events.sessions.update', [$organizer, $event, $session]), $this->validPayload())
            ->assertForbidden();
    }

    public function test_owner_gets_403_on_edit_form_of_cancelled_event(): void
    {
        [$owner, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Owner, eventCancelled: true);

        $this->actingAs($owner)
            ->get(route('organizers.events.sessions.edit', [$organizer, $event, $session]))
            ->assertForbidden();
    }

    public function test_session_of_another_event_returns_404(): void
    {
        [$admin, $organizer, $event] = $this->sessionWithMember(OrganizerRole::Admin);
        // 同一個主辦方、另一個活動的場次
        $otherSession = EventSession::factory()
            ->for(Event::factory()->for($organizer))
            ->create();

        $this->actingAs($admin)
            ->get(route('organizers.events.sessions.edit', [$organizer, $event, $otherSession]))
            ->assertNotFound();
    }

    public function test_update_converts_local_times_and_resyncs_event(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin, [
            'starts_at' => '2026-12-31 11:00:00',
            'ends_at' => '2026-12-31 14:00:00',
        ]);
        EventSession::factory()->for($event)->create([
            'starts_at' => '2026-12-20 11:00:00',
            'ends_at' => '2026-12-20 14:00:00',
        ]);
        $event->syncLastSessionEndsAt();

        // 把最晚的場次提前到 12/10，last_session_ends_at 要「變小」成另一場的 12/20
        $this->actingAs($admin)
            ->put(route('organizers.events.sessions.update', [$organizer, $event, $session]), $this->validPayload([
                'timezone' => 'Asia/Taipei',
                'starts_at' => '2026-12-10T19:00',
                'ends_at' => '2026-12-10T22:00',
                // 偷塞的欄位要被忽略
                'event_id' => Event::factory()->create()->id,
                'status' => EventSessionStatus::Cancelled->value,
            ]))
            ->assertRedirect(route('organizers.events.show', [$organizer, $event]));

        $session->refresh();
        $this->assertSame($event->id, $session->event_id);
        $this->assertSame(EventSessionStatus::Scheduled, $session->status);
        $this->assertSame('新場館', $session->venue_name);
        $this->assertSame('2026-12-10 11:00:00', $session->starts_at->toDateTimeString());
        $this->assertSame('2026-12-20 14:00:00', $event->fresh()?->last_session_ends_at?->toDateTimeString());
    }

    public function test_started_session_can_be_updated_without_changing_start_time(): void
    {
        // 台北 13:00 開始、已開始一小時；資料庫的值帶秒數，表單只會送回到「分」
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin, [
            'timezone' => 'Asia/Taipei',
            'starts_at' => '2026-10-30 05:00:45',
            'ends_at' => '2026-10-30 08:00:00',
        ]);

        $this->actingAs($admin)
            ->put(route('organizers.events.sessions.update', [$organizer, $event, $session]), $this->validPayload([
                'starts_at' => '2026-10-30T13:00',
                'ends_at' => '2026-10-30T17:00',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('新場館', $session->fresh()?->venue_name);
    }

    public function test_rejects_moving_start_time_to_past(): void
    {
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin);

        // 台北 12:00 已過去
        $this->actingAs($admin)
            ->put(route('organizers.events.sessions.update', [$organizer, $event, $session]), $this->validPayload([
                'starts_at' => '2026-10-30T12:00',
                'ends_at' => '2026-10-30T15:00',
            ]))
            ->assertSessionHasErrors(['starts_at' => '開始時間必須晚於現在']);
    }

    public function test_changing_only_timezone_counts_as_changing_start_time(): void
    {
        // 台北 13:00 = UTC 05:00，已開始
        [$admin, $organizer, $event, $session] = $this->sessionWithMember(OrganizerRole::Admin, [
            'timezone' => 'Asia/Taipei',
            'starts_at' => '2026-10-30 05:00:00',
            'ends_at' => '2026-10-30 08:00:00',
        ]);

        // 牆上時間不變，但東京 13:00 = UTC 04:00，實際時刻提早了一小時，且已過去
        $this->actingAs($admin)
            ->put(route('organizers.events.sessions.update', [$organizer, $event, $session]), $this->validPayload([
                'timezone' => 'Asia/Tokyo',
                'starts_at' => '2026-10-30T13:00',
                'ends_at' => '2026-10-30T16:00',
            ]))
            ->assertSessionHasErrors(['starts_at' => '開始時間必須晚於現在']);
    }

    /**
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
        $session = ($sessionCancelled ? $sessionFactory->cancelled() : $sessionFactory)->create($sessionAttributes);

        return [$user, $organizer, $event, $session];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'venue_name' => '新場館',
            'timezone' => 'Asia/Taipei',
            'starts_at' => '2026-12-25T19:30',
            'ends_at' => '2026-12-25T22:30',
            ...$overrides,
        ];
    }
}
