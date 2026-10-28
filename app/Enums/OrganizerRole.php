<?php

namespace App\Enums;

enum OrganizerRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Scanner = 'scanner';

    /**
     * 角色權限由上往下包含：owner ⊇ admin ⊇ scanner（ADR 0004）
     */
    public function atLeast(self $role): bool
    {
        return $this->level() >= $role->level();
    }

    private function level(): int
    {
        return match ($this) {
            self::Owner => 3,
            self::Admin => 2,
            self::Scanner => 1,
        };
    }
}
