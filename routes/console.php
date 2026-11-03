<?php

use App\Console\Commands\TelegramExpiringTransactions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command(TelegramExpiringTransactions::class)
    ->dailyAt('07:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
