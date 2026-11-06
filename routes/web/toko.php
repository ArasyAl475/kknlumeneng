<?php

use App\Http\Controllers\Toko\BeliController;
use App\Http\Controllers\Toko\EksporController;
use App\Http\Controllers\Toko\HasilController;
use App\Http\Controllers\Toko\KatalogController;
use App\Http\Controllers\Toko\ProdukController;
use App\Http\Controllers\Toko\TokoController;
use App\Services\Toko\DaftarMarketplace;
use Illuminate\Support\Facades\Route;

// Rute publik modul Toko. Nama rute: toko.* (toko.index dipakai config/modul.php).
Route::get('/toko', [TokoController::class, 'index'])->name('toko.index');

// Rekap hasil panen. Dipasang sebelum rute {marketplace} agar "hasil" tidak pernah dianggap marketplace
// (rute marketplace juga dibatasi whereIn, ini lapis pengaman kedua).
Route::get('/toko/hasil', [HasilController::class, 'index'])->name('toko.hasil');
// Pembuatan berkas lebih berat dari halaman biasa, jadi dibatasi lajunya.
Route::get('/toko/hasil/excel', EksporController::class)->middleware('throttle:20,1')->name('toko.hasil.excel');
// Katalog sederhana dari desain Stitch; etalase utama tetap /toko.
Route::get('/toko/katalog', [KatalogController::class, 'index'])->name('toko.katalog');

Route::prefix('toko/{marketplace}')->whereIn('marketplace', DaftarMarketplace::KUNCI)->group(function () {
    Route::get('/', [TokoController::class, 'marketplace'])->name('toko.marketplace');
    Route::get('{produk:slug}', ProdukController::class)->name('toko.produk');
    // Pengalih ke marketplace agar klik bisa dihitung; tujuannya selalu tautan dari basis data.
    Route::get('{produk:slug}/beli', BeliController::class)->middleware('throttle:30,1')->name('toko.beli');
});
