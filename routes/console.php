<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Optional: process patient import files from storage/app/imports/scheduled every 5 minutes.
// Uses --sync so no queue worker is required. Replace 2 with your user ID to notify.
// Schedule::command('patients:import-scheduled', ['--user' => 2, '--sync'])->everyFiveMinutes();
