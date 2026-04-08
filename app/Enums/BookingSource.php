<?php

namespace App\Enums;

enum BookingSource: string
{
    case Web = 'web';
    case WalkIn = 'walk_in';
    case Admin = 'admin';
}
