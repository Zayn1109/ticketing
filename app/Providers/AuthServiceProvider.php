<?php

namespace App\Providers;

use App\Enums\OrganizerRole;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureGates();
    }

    /**
     * 主辦方後台權限：ability => 最低角色（docs/permissions.md、ADR 0004）
     */
    protected function configureGates(): void
    {
        $abilities = [
            'organizer.update' => OrganizerRole::Owner,
            'member.viewAny' => OrganizerRole::Admin,
            'member.manageScanners' => OrganizerRole::Admin,
            'member.manage' => OrganizerRole::Owner,
            'event.viewAny' => OrganizerRole::Scanner,
            'event.manage' => OrganizerRole::Admin,
            'event.publish' => OrganizerRole::Admin,
            'event.cancel' => OrganizerRole::Owner,
            'order.viewAny' => OrganizerRole::Admin,
            'ticket.checkIn' => OrganizerRole::Scanner,
            'scanLog.viewAny' => OrganizerRole::Admin,
        ];

        foreach ($abilities as $ability => $minimum) {
            Gate::define(
                $ability,
                fn (User $user, Organizer $organizer): bool => $user->roleIn($organizer)?->atLeast($minimum) ?? false,
            );
        }
    }
}
