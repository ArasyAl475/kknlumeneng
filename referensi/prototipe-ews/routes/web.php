<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EwsController;
use Illuminate\Support\Facades\Route;

// ----- Publik -----
Route::get('/', [EwsController::class, 'index'])->name('dashboard');
Route::get('/data/terbaru', [EwsController::class, 'getLatestData'])->name('data.terbaru');

// ----- Login admin -----
Route::get('/masuk', [AuthController::class, 'formLogin'])->name('login');
Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.proses');
Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');

// ----- Khusus admin (wajib login) -----
// Login wajib jika EWS_ADMIN_LOGIN=true di .env (WAJIB true begitu website online dan akun admin sudah dibuat).
Route::prefix('admin')->middleware(config('ews.admin_wajib_login') ? ['auth'] : [])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/validasi/{sensorData}', [AdminController::class, 'validasi'])->name('admin.validasi');
    Route::post('/awas/{wilayah}/cabut', [AdminController::class, 'cabutAwas'])->name('admin.cabut');
    Route::post('/ambang', [AdminController::class, 'simpanAmbang'])->name('admin.ambang');
    Route::post('/wilayah', [AdminController::class, 'tambahWilayah'])->name('admin.wilayah.tambah');
    Route::delete('/wilayah/{wilayah}', [AdminController::class, 'hapusWilayah'])->name('admin.wilayah.hapus');

    Route::get('/simulator', [EwsController::class, 'simulator'])->name('simulator');
    Route::post('/simulator', [EwsController::class, 'storeData'])->name('simulator.store');
});
