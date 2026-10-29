<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('consultations:scan')->everyMinute()->withoutOverlapping(2);
Schedule::command('automation:work --limit=20')->everyMinute()->withoutOverlapping(2);
