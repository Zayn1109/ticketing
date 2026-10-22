<?php

namespace App\Enums;

enum EventSessionStatus: string
{
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';
}
