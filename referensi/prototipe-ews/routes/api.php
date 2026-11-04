<?php

use App\Http\Controllers\EwsController;
use Illuminate\Support\Facades\Route;

// Alamat untuk alat/sensor (wajib header X-API-KEY):
//   POST /api/sensor  -> kirim data sensor
//   GET  /api/ping    -> tes koneksi (mengembalikan jam server dan daftar wilayah)
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/sensor', [EwsController::class, 'storeData'])->name('api.sensor.store');
    Route::get('/ping', [EwsController::class, 'ping'])->name('api.ping');
});
