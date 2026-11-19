<?php

use App\Http\Controllers\Admin\Akun\SandiController;
use Illuminate\Support\Facades\Route;

// Awalan /admin, nama admin.*, dan middleware web + auth + admin otomatis dari bootstrap/app.php.
Route::prefix('akun')->name('akun.')->group(function () {
    Route::get('sandi', [SandiController::class, 'edit'])->name('sandi.edit');
    Route::put('sandi', [SandiController::class, 'update'])->middleware('throttle:atur-sandi')->name('sandi.update');
});
