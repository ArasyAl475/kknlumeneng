<?php

use Illuminate\Support\Facades\Schedule;

// Berjalan bila hosting menyediakan cron `php artisan schedule:run` (lihat docs/DEPLOY.md).
Schedule::command('ews:bersihkan')->dailyAt('02:00')->withoutOverlapping();
// Token akses Shopee hanya hidup ±4 jam; dicek tiap jam dan diperpanjang bila sisa umurnya < 90 menit.
Schedule::command('toko:perpanjang-token-shopee')->hourly()->withoutOverlapping();

// Tarik nama, harga, dan deskripsi produk dari Shopee tiap dini hari (data Shopee menimpa data di sini).
Schedule::command('toko:sinkron-shopee')->dailyAt('03:00')->withoutOverlapping();

// Untuk hosting yang hanya punya cron: berlangganan ~50 detik tiap menit. Pesan di sela-sela
// ditampung broker (sesi tidak dibersihkan). Bila ews:mqtt dijalankan terus-menerus, biarkan EWS_MQTT_JADWAL=false.
Schedule::command('ews:mqtt --detik=50')->everyMinute()->withoutOverlapping(2)
    ->when(fn () => config('ews.mqtt.aktif') && config('ews.mqtt.jadwal'));
