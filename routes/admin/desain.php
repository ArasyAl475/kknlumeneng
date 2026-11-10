<?php

use App\Http\Controllers\Admin\Desain\SlotGambarController;
use Illuminate\Support\Facades\Route;

// Menu Desain Web: gambar tata letak situs. Awalan /admin dan middleware auth + admin sudah otomatis.
Route::prefix('desain')->name('desain.')->group(function () {
    Route::get('/', [SlotGambarController::class, 'index'])->name('index');
    Route::put('slot/{slot}', [SlotGambarController::class, 'update'])->middleware('throttle:30,1')->name('update');
    Route::post('slot/{slot}/pulihkan', [SlotGambarController::class, 'pulihkan'])->name('pulihkan');
});
