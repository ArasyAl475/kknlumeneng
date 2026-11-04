<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Support\Modul;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(): View
    {
        return view('landing.index', ['modul' => Modul::semua()]);
    }
}
