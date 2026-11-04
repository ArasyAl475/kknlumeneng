<?php

namespace App\Http\Controllers\Admin\Ews;

use App\Http\Controllers\Controller;
use App\Models\Ews\Aktivitas;
use Illuminate\View\View;

class AktivitasController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.ews.aktivitas', ['aktivitas' => Aktivitas::query()->latest('id')->paginate(50)]);
    }
}
