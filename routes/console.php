<?php

use App\Actions\Admin\SendLeadFollowUpDigest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Each morning, notify admins of lead follow-ups that are overdue or due today.
// Requires the server cron entry `* * * * * php artisan schedule:run` (or
// `php artisan schedule:work` in development).
Schedule::call(fn () => SendLeadFollowUpDigest::run())
    ->dailyAt('08:00')
    ->name('lead-follow-up-digest')
    ->withoutOverlapping();
