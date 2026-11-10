<?php

use App\Http\Controllers\Profil\BeritaController;
use App\Http\Controllers\Profil\GaleriController;
use App\Http\Controllers\Profil\ProfilController;
use Illuminate\Support\Facades\Route;

// Rute publik modul Profil Desa. Nama rute: profil.* dan berita.* (dipakai config/modul.php).
Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
Route::get('/profil/visi-misi', [ProfilController::class, 'visiMisi'])->name('profil.visi-misi');
Route::get('/profil/pemerintahan', [ProfilController::class, 'pemerintahan'])->name('profil.pemerintahan');

Route::get('/berita', [BeritaController::class, 'index'])->name('berita.index');
Route::get('/berita/{slug}', [BeritaController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('berita.show');

Route::get('/galeri', [GaleriController::class, 'index'])->name('galeri.index');
Route::get('/galeri/{foto}', [GaleriController::class, 'show'])->whereNumber('foto')->name('galeri.show');
