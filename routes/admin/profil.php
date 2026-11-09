<?php

use App\Http\Controllers\Admin\Profil\BeritaController;
use App\Http\Controllers\Admin\Profil\GaleriController;
use App\Http\Controllers\Admin\Profil\GambarIsiController;
use App\Http\Controllers\Admin\Profil\LembagaController;
use App\Http\Controllers\Admin\Profil\PengaturanController;
use App\Http\Controllers\Admin\Profil\PerangkatController;
use App\Services\Profil\BagianProfil;
use Illuminate\Support\Facades\Route;

// Rute admin modul Profil Desa. Awalan /admin dan middleware sudah otomatis. Tulis nama profil.* (menjadi admin.profil.*)
Route::prefix('profil')->name('profil.')->group(function () {
    // Satu rute bernama per bagian, agar menu sidebar cukup menyebut nama rute tanpa parameter.
    foreach (BagianProfil::kunci() as $bagian) {
        Route::get($bagian, [PengaturanController::class, 'edit'])->defaults('bagian', $bagian)->name("{$bagian}.edit");
        Route::put($bagian, [PengaturanController::class, 'update'])->defaults('bagian', $bagian)->name("{$bagian}.update");
    }

    Route::resource('perangkat', PerangkatController::class)->except('show')->parameters(['perangkat' => 'perangkat']);
    Route::resource('lembaga', LembagaController::class)->except('show')->parameters(['lembaga' => 'lembaga']);

    Route::resource('berita', BeritaController::class)->except('show')->parameters(['berita' => 'berita']);
    Route::get('berita/{berita}/pratinjau', [BeritaController::class, 'pratinjau'])->name('berita.pratinjau');

    Route::resource('galeri', GaleriController::class)->except('show')->parameters(['galeri' => 'foto']);

    // Foto yang disisipkan di tengah tulisan lewat editor. Dibatasi agar tidak bisa dibanjiri unggahan.
    Route::post('gambar-isi', GambarIsiController::class)->middleware('throttle:60,1')->name('gambar-isi');
});
