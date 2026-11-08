<?php

use App\Http\Controllers\Edukasi\MateriController;
use Illuminate\Support\Facades\Route;

// Rute publik modul Edukasi. Nama rute: edukasi.*
Route::get('/edukasi', [MateriController::class, 'index'])->name('edukasi.index');
Route::get('/edukasi/{slug}', [MateriController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('edukasi.show');
