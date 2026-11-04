<?php

use App\Http\Controllers\Auth\MasukController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [MasukController::class, 'tampil'])->name('login');
    Route::post('/masuk', [MasukController::class, 'proses'])->middleware('throttle:masuk')->name('masuk.proses');
});

Route::post('/keluar', [MasukController::class, 'keluar'])->middleware('auth')->name('keluar');
