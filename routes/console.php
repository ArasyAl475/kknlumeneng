<?php

use Illuminate\Support\Facades\Schedule;

// Berjalan bila hosting menyediakan cron `php artisan schedule:run` (lihat docs/DEPLOY.md).
Schedule::command('ews:cek-alat')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('ews:bersihkan')->dailyAt('02:00')->withoutOverlapping();
