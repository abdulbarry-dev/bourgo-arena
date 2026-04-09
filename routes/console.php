<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('terminals:check-offline')->everyMinute();
Schedule::command('analytics:aggregate-revenue')->dailyAt('03:00');
Schedule::command('analytics:aggregate-occupancy')->dailyAt('03:00');
