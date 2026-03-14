<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('rmcp:notify-expiring-documents --days=30')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::command('rmcp:escalate-overdue-cases')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('rmcp:run-ongoing-screening --limit=200')
    ->hourly()
    ->withoutOverlapping();
