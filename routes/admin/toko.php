<?php

use App\Http\Controllers\Admin\Toko\PanenJamurController;
use App\Http\Controllers\Admin\Toko\PanenKopiController;
use App\Http\Controllers\Admin\Toko\PenjualanJamurController;
use Illuminate\Support\Facades\Route;

// Rute admin modul Toko. Awalan /admin dan middleware sudah otomatis. Tulis nama toko.* (menjadi admin.toko.*)
Route::prefix('toko')->name('toko.')->group(function () {
    Route::resource('panen-kopi', PanenKopiController::class)->except('show')->parameters(['panen-kopi' => 'panenKopi']);
    Route::resource('panen-jamur', PanenJamurController::class)->except('show')->parameters(['panen-jamur' => 'panenJamur']);
    Route::resource('penjualan-jamur', PenjualanJamurController::class)->except('show')->parameters(['penjualan-jamur' => 'penjualanJamur']);
});
