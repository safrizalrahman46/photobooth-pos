<?php

use App\Services\ActivityLogger;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('activity-logs:purge', function (ActivityLogger $activityLogger) {
    $deletedCount = $activityLogger->purgeOlderThanDays(90);

    $this->info(sprintf('Purged %d activity logs older than 90 days.', $deletedCount));
})->purpose('Purge activity logs older than 90 days');

Schedule::command('activity-logs:purge')->weeklyOn(1, '02:00');
