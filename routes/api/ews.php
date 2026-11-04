<?php

use App\Http\Controllers\Ews\AlatController;
use App\Http\Middleware\Ews\PeriksaKunciAlat;
use Illuminate\Support\Facades\Route;

// Kontrak alat: docs/ews/API_ALAT.md. Throttle diletakkan sebelum pemeriksaan kunci agar
// tebakan kunci juga ikut dibatasi.
Route::middleware(['throttle:60,1', PeriksaKunciAlat::class])->group(function () {
    Route::post('/sensor', [AlatController::class, 'simpan'])->name('ews.api.sensor');
    Route::get('/ping', [AlatController::class, 'ping'])->name('ews.api.ping');
});
