<?php

namespace Tests\Feature\Organizer;

use App\Enums\OrganizerRole;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Organizer $organizerA;

    private Event $eventA;

    private Organizer $organizerB;

    private User $memberOfB;

    protected function setUp(): void
    {
        parent::setUp();

        // 主辦方 A：之後所有請求都打它的網址
        $this->organizerA = Organizer::factory()->create();
        $this->eventA = Event::factory()->for($this->organizerA)->create();

        // 主辦方 B：用最高權限 owner，證明角色再高也進不了別的租戶
        $this->memberOfB = User::factory()->create();
        $this->organizerB = Organizer::factory()->withMember($this->memberOfB, OrganizerRole::Owner)->create();
    }

    public function test_other_tenant_member_gets_404_on_events_index(): void
    {
        $this->actingAs($this->memberOfB)
            ->get(route('organizers.events.index', $this->organizerA))
            ->assertNotFound();
    }

    public function test_other_tenant_member_gets_404_on_events_show(): void
    {
        $this->actingAs($this->memberOfB)
            ->get(route('organizers.events.show', [$this->organizerA, $this->eventA]))
            ->assertNotFound();
    }

    public function test_member_cannot_reach_other_tenant_event_through_own_organizer_url(): void
    {
        // 自己主辦方的網址 + 別人的活動 id，靠 scopeBindings 回 404
        $this->actingAs($this->memberOfB)
            ->get(route('organizers.events.show', [$this->organizerB, $this->eventA]))
            ->assertNotFound();
    }
}
