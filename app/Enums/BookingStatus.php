<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Paid = 'paid';
    case CheckedIn = 'checked_in';
    case InQueue = 'in_queue';
    case InSession = 'in_session';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public static function activeStatuses(): array
    {
        return [
            self::Pending->value,
            self::Confirmed->value,
            self::Paid->value,
            self::CheckedIn->value,
            self::InQueue->value,
            self::InSession->value,
        ];
    }
}
