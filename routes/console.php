<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notifications:send-sewa-expiry-reminders')->dailyAt('08:00');
Schedule::command('notifications:send-birthday-greetings')->dailyAt('08:10');
Schedule::command('reservations:expire')->dailyAt('00:05')->withoutOverlapping();
Schedule::command('private-files:cleanup --limit=100')->hourly()->withoutOverlapping();
