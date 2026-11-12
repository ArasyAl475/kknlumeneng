<?php

use App\Http\Controllers\Toko\VersiHasilController;
use Illuminate\Support\Facades\Route;

// Rute API modul Toko. Awalan /api sudah otomatis.

// Sidik data hasil panen untuk pembaruan otomatis halaman Hasil Panen dan daftar admin.
// Di grup api karena ditanya tiap beberapa detik: tanpa sesi berarti tidak menulis ke basis data,
// dan tab admin yang dibiarkan terbuka tidak membuat sesi login hidup terus.
Route::get('/toko/hasil/versi', VersiHasilController::class)->middleware('throttle:120,1')->name('toko.hasil.versi');
