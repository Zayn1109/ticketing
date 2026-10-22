<?php

namespace App\Enums;

enum OrganizerRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Scanner = 'scanner';
}
