<?php

use App\Http\Controllers\Admin\BerandaController;
use Illuminate\Support\Facades\Route;

Route::get('/', BerandaController::class)->name('beranda');
