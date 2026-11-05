<?php

use App\Http\Controllers\Toko\HasilController;
use Illuminate\Support\Facades\Route;

// Rute publik modul Toko. Nama rute: toko.*
// /toko disiapkan untuk etalase nanti; sementara diarahkan ke rekap agar menu tetap berguna.
Route::redirect('/toko', '/toko/hasil')->name('toko.index');
Route::get('/toko/hasil', [HasilController::class, 'index'])->name('toko.hasil');
