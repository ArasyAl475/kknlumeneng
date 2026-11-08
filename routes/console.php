<?php

use Illuminate\Support\Facades\Schedule;

// Berjalan bila hosting menyediakan cron `php artisan schedule:run` (lihat docs/DEPLOY.md).
Schedule::command('ews:cek-alat')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('ews:bersihkan')->dailyAt('02:00')->withoutOverlapping();
// Token akses Shopee hanya hidup ±4 jam; dicek tiap jam dan diperpanjang bila sisa umurnya < 90 menit.
Schedule::command('toko:perpanjang-token-shopee')->hourly()->withoutOverlapping();
