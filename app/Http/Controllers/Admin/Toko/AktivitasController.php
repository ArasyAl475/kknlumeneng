<?php

namespace App\Http\Controllers\Admin\Toko;

use App\Http\Controllers\Controller;
use App\Models\Toko\Aktivitas;
use Illuminate\View\View;

class AktivitasController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.toko.aktivitas', ['aktivitas' => Aktivitas::query()->latest('id')->paginate(50)]);
    }
}
