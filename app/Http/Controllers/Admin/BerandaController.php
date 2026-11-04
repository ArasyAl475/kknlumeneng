<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Modul;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.beranda', ['modul' => Modul::semua()]);
    }
}
