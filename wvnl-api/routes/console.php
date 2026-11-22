<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Motions are voted on after they are submitted: fill in party votes for linked statements daily.
Schedule::command('motions:refresh-votes')->dailyAt('06:30')->withoutOverlapping();
