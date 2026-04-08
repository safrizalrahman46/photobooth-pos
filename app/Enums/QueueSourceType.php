<?php

namespace App\Enums;

enum QueueSourceType: string
{
    case Booking = 'booking';
    case WalkIn = 'walk_in';
}
