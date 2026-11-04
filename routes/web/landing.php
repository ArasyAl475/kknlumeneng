<?php

use App\Http\Controllers\Landing\BerandaController;
use Illuminate\Support\Facades\Route;

Route::get('/', BerandaController::class)->name('landing');
