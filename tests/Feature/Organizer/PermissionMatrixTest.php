<?php

namespace Tests\Feature\Organizer;

use App\Enums\OrganizerRole;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 對照 docs/permission.md，故意不讓 AuthServiceProvider，讓測試成為獨立的規格
     *
     * @return array<string, array{string, bool, bool, bool}>
     */
    public static function matrix(): array
    {
        // [ability, owner, admin, scanner]
        return [
            'organizer.update' => ['organizer.update', true, false, false],
            'member.viewAny' => ['member.viewAny', true, true, false],
            'member.manageScanners' => ['member.manageScanners', true, true, false],
            'member.manage' => ['member.manage', true, false, false],
            'event.viewAny' => ['event.viewAny', true, true, true],
            'event.manage' => ['event.manage', true, true, false],
            'event.publish' => ['event.publish', true, true, false],
            'event.cancel' => ['event.cancel', true, false, false],
            'order.viewAny' => ['order.viewAny', true, true, false],
            'ticket.checkIn' => ['ticket.checkIn', true, true, true],
            'scanLog.viewAny' => ['scanLog.viewAny', true, true, false],
        ];
    }

    #[DataProvider('matrix')]
    public function test_role_permissions_match_matrix(string $ability, bool $owner, bool $admin, bool $scanner): void
    {
        $expected = [
            OrganizerRole::Owner->value => $owner,
            OrganizerRole::Admin->value => $admin,
            OrganizerRole::Scanner->value => $scanner,
        ];

        foreach (OrganizerRole::cases() as $role) {
            $user = User::factory()->create();
            $organizer = Organizer::factory()->withMember($user, $role)->create();

            $this->assertSame(
                $expected[$role->value],
                $user->can($ability, $organizer),
                "{$role->value} 對 {$ability} 的權限與矩陣不符合",
            );
        }
    }

    #[DataProvider('matrix')]
    public function test_non_member_has_no_permission(string $ability): void
    {
        $user = User::factory()->create();
        $organizer = Organizer::factory()->create();

        $this->assertFalse($user->can($ability, $organizer));
    }

    #[DataProvider('matrix')]
    public function test_role_does_not_carry_over_to_other_organizer(string $ability): void
    {
        $user = User::factory()->create();
        Organizer::factory()->withMember($user, OrganizerRole::Owner)->create();
        $other = Organizer::factory()->create();

        $this->assertFalse($user->can($ability, $other));
    }
}
