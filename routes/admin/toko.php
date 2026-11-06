<?php

use App\Http\Controllers\Admin\Toko\AktivitasController;
use App\Http\Controllers\Admin\Toko\BerandaController;
use App\Http\Controllers\Admin\Toko\FotoProdukController;
use App\Http\Controllers\Admin\Toko\MarketplaceController;
use App\Http\Controllers\Admin\Toko\PengaturanController;
use App\Http\Controllers\Admin\Toko\ProdukController;
use App\Services\Toko\DaftarMarketplace;
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
});
