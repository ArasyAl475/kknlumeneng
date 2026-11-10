<?php

use App\Http\Controllers\Admin\Toko\AktivitasController;
use App\Http\Controllers\Admin\Toko\BerandaController;
use App\Http\Controllers\Admin\Toko\FotoProdukController;
use App\Http\Controllers\Admin\Toko\ImporHasilController;
use App\Http\Controllers\Admin\Toko\MarketplaceController;
use App\Http\Controllers\Admin\Toko\PanenJamurController;
use App\Http\Controllers\Admin\Toko\PanenKopiController;
use App\Http\Controllers\Admin\Toko\PengaturanController;
use App\Http\Controllers\Admin\Toko\PenjualanJamurController;
use App\Http\Controllers\Admin\Toko\ProdukController;
use App\Services\Toko\DaftarMarketplace;
use App\Services\Toko\ImporExcelHasil;
use Illuminate\Support\Facades\Route;

// Awalan /admin dan middleware web + auth + admin otomatis dari bootstrap/app.php.
Route::prefix('toko')->name('toko.')->group(function () {
    Route::get('/', BerandaController::class)->name('beranda');

    Route::get('marketplace/{marketplace}', [MarketplaceController::class, 'show'])
        ->whereIn('marketplace', DaftarMarketplace::KUNCI)->name('marketplace.show');
    Route::put('marketplace/{marketplace}', [MarketplaceController::class, 'update'])
        ->whereIn('marketplace', DaftarMarketplace::KUNCI)->name('marketplace.update');

    Route::resource('produk', ProdukController::class)->except('show')->parameters(['produk' => 'produk']);
    Route::post('produk/{id}/pulihkan', [ProdukController::class, 'pulihkan'])->whereNumber('id')->name('produk.pulihkan');

    // Kepemilikan foto diperiksa di controller; scopeBindings menebak relasi jamak "fotos".
    Route::post('produk/{produk}/foto', [FotoProdukController::class, 'store'])->name('foto.store');
    Route::delete('produk/{produk}/foto/{foto}', [FotoProdukController::class, 'destroy'])->name('foto.destroy');
    Route::post('produk/{produk}/foto/{foto}/{arah}', [FotoProdukController::class, 'geser'])
        ->whereIn('arah', ['naik', 'turun'])->name('foto.geser');

    Route::get('pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan.edit');
    Route::put('pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');

    Route::get('aktivitas', AktivitasController::class)->name('aktivitas');

    // Pencatatan hasil panen (tampil di halaman publik /toko/hasil).
    Route::resource('panen-kopi', PanenKopiController::class)->except('show')->parameters(['panen-kopi' => 'panenKopi']);
    Route::resource('panen-jamur', PanenJamurController::class)->except('show')->parameters(['panen-jamur' => 'panenJamur']);
    Route::resource('penjualan-jamur', PenjualanJamurController::class)->except('show')->parameters(['penjualan-jamur' => 'penjualanJamur']);

    // Impor Excel: hanya menambah catatan baru. Pratinjau dulu, baru Simpan.
    Route::prefix('impor/{jenis}')->name('impor.')->whereIn('jenis', ImporExcelHasil::kunci())->group(function () {
        Route::get('/', [ImporHasilController::class, 'show'])->name('show');
        Route::get('templat', [ImporHasilController::class, 'templat'])->name('templat');
        Route::post('pratinjau', [ImporHasilController::class, 'pratinjau'])->name('pratinjau');
        Route::post('simpan', [ImporHasilController::class, 'simpan'])->name('simpan');
    });
});
