<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Telegram reminders go out only when the bot is configured
Schedule::command('salon:bot-reminders')->everyFiveMinutes()->when(fn () => filled(config('nutgram.token')));
