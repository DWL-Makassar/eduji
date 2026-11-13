<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Situs demo direset tiap malam (butuh cron: * * * * * php artisan schedule:run). Tidak berjalan bila DEMO_MODE mati.
Schedule::command('demo:reset')->dailyAt('00:00')->when(fn () => config('platform.demo.enabled'))->withoutOverlapping();
