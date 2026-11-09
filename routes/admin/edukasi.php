<?php

use App\Http\Controllers\Admin\Edukasi\KategoriController;
use App\Http\Controllers\Admin\Edukasi\LangkahController;
use App\Http\Controllers\Admin\Edukasi\MateriController;
use Illuminate\Support\Facades\Route;

// Rute admin modul Edukasi. Awalan /admin dan middleware sudah otomatis. Tulis nama edukasi.* (menjadi admin.edukasi.*)
Route::prefix('edukasi')->name('edukasi.')->group(function () {
    Route::resource('kategori', KategoriController::class)->except('show')->parameters(['kategori' => 'kategori']);

    Route::resource('materi', MateriController::class)->except('show')->parameters(['materi' => 'materi']);
    Route::get('materi/{materi}/pratinjau', [MateriController::class, 'pratinjau'])->name('materi.pratinjau');
    Route::post('materi/{materi}/terbitkan', [MateriController::class, 'terbitkan'])->name('materi.terbitkan');
    Route::post('materi/{materi}/tarik', [MateriController::class, 'tarik'])->name('materi.tarik');

    Route::get('materi/{materi}/langkah/create', [LangkahController::class, 'create'])->name('langkah.create');
    Route::post('materi/{materi}/langkah', [LangkahController::class, 'store'])->name('langkah.store');
    Route::get('langkah/{langkah}/edit', [LangkahController::class, 'edit'])->name('langkah.edit');
    Route::put('langkah/{langkah}', [LangkahController::class, 'update'])->name('langkah.update');
    Route::delete('langkah/{langkah}', [LangkahController::class, 'destroy'])->name('langkah.destroy');
    Route::post('langkah/{langkah}/pindah', [LangkahController::class, 'pindah'])->name('langkah.pindah');
});
