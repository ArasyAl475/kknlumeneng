<?php

use App\Http\Controllers\Ews\DasborController;
use Illuminate\Support\Facades\Route;

Route::get('/ews', [DasborController::class, 'index'])->name('ews.index');
