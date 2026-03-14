<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sweep stuck recharges every 5 minutes — safety net for lost callbacks
Schedule::command('recharges:sweep --minutes=10')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
