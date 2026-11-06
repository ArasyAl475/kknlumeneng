<?php

use App\Http\Controllers\Toko\BeliController;
use App\Http\Controllers\Toko\ProdukController;
use App\Http\Controllers\Toko\TokoController;
use App\Services\Toko\DaftarMarketplace;
use Illuminate\Support\Facades\Route;

// Rute publik modul Toko. Nama rute: toko.* (toko.index dipakai config/modul.php).
Route::get('/toko', [TokoController::class, 'index'])->name('toko.index');

Route::prefix('toko/{marketplace}')->whereIn('marketplace', DaftarMarketplace::KUNCI)->group(function () {
    Route::get('/', [TokoController::class, 'marketplace'])->name('toko.marketplace');
    Route::get('{produk:slug}', ProdukController::class)->name('toko.produk');
    // Pengalih ke marketplace agar klik bisa dihitung; tujuannya selalu tautan dari basis data.
    Route::get('{produk:slug}/beli', BeliController::class)->middleware('throttle:30,1')->name('toko.beli');
});
