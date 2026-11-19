<?php

use App\Http\Controllers\Auth\AturUlangSandiController;
use App\Http\Controllers\Auth\LupaSandiController;
use App\Http\Controllers\Auth\MasukController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [MasukController::class, 'tampil'])->name('login');
    Route::post('/masuk', [MasukController::class, 'proses'])->middleware('throttle:masuk')->name('masuk.proses');

    // Lupa kata sandi lewat email. Nama rute mengikuti bawaan Laravel (password.*) karena dipakai
    // oleh mekanisme atur ulang sandi Laravel.
    Route::get('/lupa-sandi', [LupaSandiController::class, 'tampil'])->name('password.request');
    Route::post('/lupa-sandi', [LupaSandiController::class, 'kirim'])->middleware('throttle:lupa-sandi')->name('password.email');
    Route::get('/atur-ulang-sandi/{token}', [AturUlangSandiController::class, 'tampil'])->name('password.reset');
    Route::post('/atur-ulang-sandi', [AturUlangSandiController::class, 'simpan'])->middleware('throttle:atur-sandi')->name('password.update');
});

Route::post('/keluar', [MasukController::class, 'keluar'])->middleware('auth')->name('keluar');
