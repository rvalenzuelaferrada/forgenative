<?php

use App\Jobs\RefreshMenuBarStatus;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new RefreshMenuBarStatus)
    ->name('refresh-menu-bar-deployment-status')
    ->everyMinute();
