<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recharge employee balances every hour (checks if it's time based on recharge settings)
Schedule::command('balances:recharge')
    ->hourly()
    ->description('Recharge employee balances based on role recharge settings');
