<?php

use App\Http\Controllers\Ews\AlatController;
use App\Http\Controllers\Ews\DasborController;
use App\Http\Middleware\Ews\PeriksaKunciAlat;
use Illuminate\Support\Facades\Route;

// Kontrak alat: docs/ews/API_ALAT.md. Throttle diletakkan sebelum pemeriksaan kunci agar
// tebakan kunci juga ikut dibatasi.
Route::middleware(['throttle:60,1', PeriksaKunciAlat::class])->group(function () {
    Route::post('/sensor', [AlatController::class, 'simpan'])->name('ews.api.sensor');
    Route::get('/ping', [AlatController::class, 'ping'])->name('ews.api.ping');
});

// Data dashboard publik. Di grup api karena dipanggil tiap beberapa detik oleh setiap pengunjung:
// tanpa sesi berarti tiap panggilan tidak menulis ke basis data dan bisa di-cache browser.
Route::get('/ews/data', [DasborController::class, 'data'])->name('ews.data');
Route::get('/ews/grafik', [DasborController::class, 'grafik'])->middleware('throttle:120,1')->name('ews.grafik');
