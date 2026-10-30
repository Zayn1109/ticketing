<?php

namespace Tests\Feature\Organizer;

use App\Enums\EventStatus;
use App\Enums\OrganizerRole;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_edit_form_with_current_values(): void
    {
        $admin = User::factory()->create();
        $organizer = Organizer::factory()->withMember($admin, OrganizerRole::Admin)->create();
        $event = Event::factory()->for($organizer)->create();

        $this->actingAs($admin)
            ->get(route('organizers.events.edit', [$organizer, $event]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('organizer/events/Edit')
                ->where('event.id', $event->id)
                ->where('event.name', $event->name)
                ->where('event.description', $event->description)
                ->missing('event.created_by')
            );
    }

    public function test_scanner_gets_403_on_edit_form(): void
    {
        $scanner = User::factory()->create();
        $organizer = Organizer::factory()->withMember($scanner, OrganizerRole::Scanner)->create();
        $event = Event::factory()->for($organizer)->create();

        $this->actingAs($scanner)
            ->get(route('organizers.events.edit', [$organizer, $event]))
            ->assertForbidden();
    }

    public function test_scanner_cannot_update_event(): void
    {
        $scanner = User::factory()->create();
        $organizer = Organizer::factory()->withMember($scanner, OrganizerRole::Scanner)->create();
        $event = Event::factory()->for($organizer)->create(['name' => '原本的名稱']);

        $this->actingAs($scanner)
            ->put(route('organizers.events.update', [$organizer, $event]), ['name' => '新名稱'])
            ->assertForbidden();

        $this->assertSame('原本的名稱', $event->fresh()->name);
    }

    public function test_update_published_event_and_ignore_protected_fields(): void
    {
        $admin = User::factory()->create();
        $organizer = Organizer::factory()->withMember($admin, OrganizerRole::Admin)->create();
        $event = Event::factory()->for($organizer)->published()->create();
        $creatorId = $event->created_by;

        // 已發布也能改名稱與說明；偷塞的 status、created_by 要被忽略
        $this->actingAs($admin)
            ->put(route('organizers.events.update', [$organizer, $event]), [
                'name' => '新名稱',
                'description' => '新說明',
                'status' => EventStatus::Draft->value,
                'created_by' => $admin->id,
            ])
            ->assertRedirect(route('organizers.events.show', [$organizer, $event]));

        $event->refresh();
        $this->assertSame('新名稱', $event->name);
        $this->assertSame('新說明', $event->description);
        $this->assertSame(EventStatus::Published, $event->status);
        $this->assertSame($creatorId, $event->created_by);
    }

    public function test_update_requires_name(): void
    {
        $admin = User::factory()->create();
        $organizer = Organizer::factory()->withMember($admin, OrganizerRole::Admin)->create();
        $event = Event::factory()->for($organizer)->create(['name' => '原本的名稱']);

        $this->actingAs($admin)
            ->put(route('organizers.events.update', [$organizer, $event]), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'The name field is required.']);

        $this->assertSame('原本的名稱', $event->fresh()->name);
    }

    public function test_owner_gets_403_on_edit_form_of_cancelled_event(): void
    {
        $owner = User::factory()->create();
        $organizer = Organizer::factory()->withMember($owner, OrganizerRole::Owner)->create();
        $event = Event::factory()->for($organizer)->cancelled()->create();

        $this->actingAs($owner)
            ->get(route('organizers.events.edit', [$organizer, $event]))
            ->assertForbidden();
    }

    public function test_owner_cannot_update_cancelled_event(): void
    {
        $owner = User::factory()->create();
        $organizer = Organizer::factory()->withMember($owner, OrganizerRole::Owner)->create();
        $event = Event::factory()->for($organizer)->cancelled()->create(['name' => '原本的名稱']);

        $this->actingAs($owner)
            ->put(route('organizers.events.update', [$organizer, $event]), ['name' => '新名稱'])
            ->assertForbidden();

        $this->assertSame('原本的名稱', $event->fresh()->name);
    }
}
