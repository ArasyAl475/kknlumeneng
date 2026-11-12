<?php

use App\Http\Controllers\Admin\Ews\AktivitasController;
use App\Http\Controllers\Admin\Ews\AlatController;
use App\Http\Controllers\Admin\Ews\AmbangController;
use App\Http\Controllers\Admin\Ews\RiwayatController;
use App\Http\Controllers\Admin\Ews\SimulatorController;
use App\Http\Controllers\Admin\Ews\TitikController;
use App\Http\Controllers\Admin\Ews\ValidasiController;
use App\Http\Controllers\Admin\Ews\WilayahController;
use Illuminate\Support\Facades\Route;

// Awalan /admin dan middleware web + auth + admin otomatis dari bootstrap/app.php.
Route::prefix('ews')->name('ews.')->group(function () {
    Route::get('validasi', [ValidasiController::class, 'index'])->name('validasi.index');
    Route::post('validasi/{pembacaan}/setujui', [ValidasiController::class, 'setujui'])->name('validasi.setujui');
    Route::post('validasi/{pembacaan}/tolak', [ValidasiController::class, 'tolak'])->name('validasi.tolak');
    Route::get('awas/{wilayah}/nyatakan', [ValidasiController::class, 'formNyatakan'])->name('awas.form-nyatakan');
    Route::post('awas/{wilayah}/nyatakan', [ValidasiController::class, 'nyatakan'])->name('awas.nyatakan');
    Route::get('awas/{wilayah}/cabut', [ValidasiController::class, 'konfirmasiCabut'])->name('awas.konfirmasi');
    Route::post('awas/{wilayah}/cabut', [ValidasiController::class, 'cabut'])->name('awas.cabut');

    Route::get('ambang', [AmbangController::class, 'edit'])->name('ambang.edit');
    Route::put('ambang', [AmbangController::class, 'update'])->name('ambang.update');

    Route::resource('wilayah', WilayahController::class)->except('show')->parameters(['wilayah' => 'wilayah']);
    Route::post('wilayah/{id}/pulihkan', [WilayahController::class, 'pulihkan'])->whereNumber('id')->name('wilayah.pulihkan');

    Route::resource('titik', TitikController::class)->except(['index', 'show'])->parameters(['titik' => 'titik']);

    Route::get('riwayat', [RiwayatController::class, 'index'])->name('riwayat.index');
    Route::get('riwayat/ekspor', [RiwayatController::class, 'ekspor'])->name('riwayat.ekspor');

    Route::get('aktivitas', AktivitasController::class)->name('aktivitas');
    Route::get('alat', AlatController::class)->name('alat');

    if (! app()->isProduction()) {
        Route::get('simulator', [SimulatorController::class, 'index'])->name('simulator.index');
        Route::post('simulator', [SimulatorController::class, 'kirim'])->name('simulator.kirim');
    }
});
