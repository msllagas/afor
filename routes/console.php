<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Removes accounts that never verified their email.
Schedule::command('model:prune')->daily();

Schedule::command('queue:prune-failed', ['--hours' => 168])->weekly();
