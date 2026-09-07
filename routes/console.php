<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('alerts:send-reminders')->everyMinute();

// Decision #3 (2026-09-07): unpaid retake registrations hard-delete
// automatically after 60 days. Daily is plenty for a day-granularity
// policy — no need to run this every minute like the alert reminders.
Schedule::command('retake:purge-unpaid')->daily();
