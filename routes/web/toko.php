<?php

use App\Http\Controllers\Toko\HasilController;
use App\Http\Controllers\Toko\KatalogController;
use Illuminate\Support\Facades\Route;

// Rute publik modul Toko (Katalog Produk & Rekap Hasil). Nama rute: toko.*
Route::get('/toko', [KatalogController::class, 'index'])->name('toko.index');
Route::get('/toko/katalog', [KatalogController::class, 'index'])->name('toko.katalog');
Route::get('/toko/hasil', [HasilController::class, 'index'])->name('toko.hasil');
