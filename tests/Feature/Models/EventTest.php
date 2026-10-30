<?php

namespace Tests\Feature\Models;

use App\Models\Event;
use App\Models\EventSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_uses_latest_scheduled_session_and_ignores_cancelled(): void
    {
        $event = Event::factory()->create();
        EventSession::factory()->for($event)->create(['ends_at' => '2026-12-01 12:00:00']);
        EventSession::factory()->for($event)->create(['ends_at' => '2026-12-10 12:00:00']);
        // 已取消的場次就算結束得最晚也不算
        EventSession::factory()->for($event)->cancelled()->create(['ends_at' => '2026-12-31 12:00:00']);

        $event->syncLastSessionEndsAt();

        $this->assertSame('2026-12-10 12:00:00', $event->fresh()->last_session_ends_at?->toDateTimeString());
    }

    public function test_sync_clears_value_when_no_scheduled_session_remains(): void
    {
        $event = Event::factory()->create();
        EventSession::factory()->for($event)->cancelled()->create();
        $event->forceFill(['last_session_ends_at' => '2026-12-01 12:00:00'])->save();

        $event->syncLastSessionEndsAt();

        $this->assertNull($event->fresh()->last_session_ends_at);
    }
}
