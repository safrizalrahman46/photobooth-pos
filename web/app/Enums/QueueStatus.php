<?php

namespace App\Enums;

enum QueueStatus: string
{
    case Waiting = 'waiting';
    case Called = 'called';
    case CheckedIn = 'checked_in';
    case InSession = 'in_session';
    case Finished = 'finished';
    case Skipped = 'skipped';
    case Cancelled = 'cancelled';
}
