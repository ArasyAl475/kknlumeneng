<?php

use Illuminate\Support\Facades\Schedule;

// Berjalan bila hosting menyediakan cron `php artisan schedule:run` (lihat docs/DEPLOY.md).
Schedule::command('ews:bersihkan')->dailyAt('02:00')->withoutOverlapping();

// Untuk hosting yang hanya punya cron: berlangganan ~50 detik tiap menit. Pesan di sela-sela
// ditampung broker (sesi tidak dibersihkan). Bila ews:mqtt dijalankan terus-menerus, biarkan EWS_MQTT_JADWAL=false.
Schedule::command('ews:mqtt --detik=50')->everyMinute()->withoutOverlapping(2)
    ->when(fn () => config('ews.mqtt.aktif') && config('ews.mqtt.jadwal'));
