<?php

namespace Tests\Feature\Organizer;

use App\Enums\EventStatus;
use App\Enums\OrganizerRole;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EventCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_create_form(): void
    {
        $admin = User::factory()->create();
        $organizer = Organizer::factory()->withMember($admin, OrganizerRole::Admin)->create();

        $this->actingAs($admin)
            ->get(route('organizers.events.create', $organizer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('organizer/events/Create')
                ->where('organizer.id', $organizer->id)
            );
    }

    public function test_scanner_gets_403_on_create_form(): void
    {
        $scanner = User::factory()->create();
        $organizer = Organizer::factory()->withMember($scanner, OrganizerRole::Scanner)->create();

        $this->actingAs($scanner)
            ->get(route('organizers.events.create', $organizer))
            ->assertForbidden();
    }

    public function test_scanner_cannot_store_event(): void
    {
        $scanner = User::factory()->create();
        $organizer = Organizer::factory()->withMember($scanner, OrganizerRole::Scanner)->create();

        $this->actingAs($scanner)
            ->post(route('organizers.events.store', $organizer), ['name' => '年度演唱會'])
            ->assertForbidden();

        $this->assertDatabaseCount('events', 0);
    }

    public function test_store_creates_draft_event_and_ignores_protected_fields(): void
    {
        $admin = User::factory()->create();
        $organizer = Organizer::factory()->withMember($admin, OrganizerRole::Admin)->create();
        $otherOrganizer = Organizer::factory()->create();
        $otherUser = User::factory()->create();

        // 前端偷塞的欄位都應該被忽略，改由後端決定
        $response = $this->actingAs($admin)
            ->post(route('organizers.events.store', $organizer), [
                'name' => '年度演唱會',
                'description' => '戶外場地，雨天照常舉行',
                'organizer_id' => $otherOrganizer->id,
                'status' => EventStatus::Published->value,
                'created_by' => $otherUser->id,
            ]);

        $event = Event::sole();

        $response->assertRedirect(route('organizers.events.show', [$organizer, $event]));
        $this->assertSame($organizer->id, $event->organizer_id);
        $this->assertSame('年度演唱會', $event->name);
        $this->assertSame('戶外場地，雨天照常舉行', $event->description);
        $this->assertSame(EventStatus::Draft, $event->status);
        $this->assertSame($admin->id, $event->created_by);
        $this->assertNull($event->published_at);
    }

    public function test_store_requires_name(): void
    {
        $admin = User::factory()->create();
        $organizer = Organizer::factory()->withMember($admin, OrganizerRole::Admin)->create();

        $this->actingAs($admin)
            ->post(route('organizers.events.store', $organizer), [])
            ->assertSessionHasErrors(['name' => 'The name field is required.']);

        $this->assertDatabaseCount('events', 0);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function tooLongFields(): array
    {
        return [
            'name 超過 255 字' => ['name', str_repeat('a', 256), 'The name field must not be greater than 255 characters.'],
            'description 超過 5000 字' => ['description', str_repeat('a', 5001), 'The description field must not be greater than 5000 characters.'],
        ];
    }

    #[DataProvider('tooLongFields')]
    public function test_store_rejects_too_long_fields(string $field, string $value, string $message): void
    {
        $admin = User::factory()->create();
        $organizer = Organizer::factory()->withMember($admin, OrganizerRole::Admin)->create();

        $this->actingAs($admin)
            ->post(route('organizers.events.store', $organizer), ['name' => '年度演唱會', $field => $value])
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('events', 0);
    }
}
