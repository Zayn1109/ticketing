<?php

namespace Database\Seeders;

use App\Enums\OrganizerRole;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Organizer;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $multi = User::factory()->create(['name' => '跨主辦成員', 'email' => 'multi@example.test']);
        User::factory()->create(['name' => '一般購票者', 'email' => 'buyer@example.test']);

        $this->seedOrganizer('甲娛樂', 'a', $multi, OrganizerRole::Admin);
        $this->seedOrganizer('乙娛樂', 'b', $multi, OrganizerRole::Scanner);
    }

    private function seedOrganizer(string $name, string $code, User $extraMember, OrganizerRole $extraRole): void
    {
        $owner = User::factory()->create(['name' => "{$name} Owner", 'email' => "owner-{$code}@example.test"]);
        $admin = User::factory()->create(['name' => "{$name} Admin", 'email' => "admin-{$code}@example.test"]);
        $scanner = User::factory()->create(['name' => "{$name} Scanner", 'email' => "scanner-{$code}@example.test"]);

        $organizer = Organizer::factory()
            ->withMember($owner, OrganizerRole::Owner)
            ->withMember($admin, OrganizerRole::Admin)
            ->withMember($scanner, OrganizerRole::Scanner)
            ->withMember($extraMember, $extraRole)
            ->create(['name' => $name]);

        Event::factory()
            ->published()
            ->for($organizer)
            ->for($owner, 'creator')
            ->has(
                EventSession::factory()
                    ->count(2)
                    ->has(TicketType::factory()->count(2)->sequence(['name' => '早鳥票'], ['name' => '全票'])),
                'sessions'
            )
            ->create(['name' => "{$name} 年度演唱會"]);

        Event::factory()
            ->for($organizer)
            ->for($owner, 'creator')
            ->create(['name' => "{$name} 籌備中活動"]);
    }
}
