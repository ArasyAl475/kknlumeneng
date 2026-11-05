<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\RekapRequest;
use App\Services\Toko\RekapHasil;
use Illuminate\View\View;

class HasilController extends Controller
{
    public function index(RekapRequest $request, RekapHasil $rekap): View
    {
        $filter = $request->validated();

        return view('toko.hasil', [
            'filter' => $filter,
            'periode' => $rekap->periode($filter),
            'pilihan' => $rekap->pilihanKopi(),
            'kopi' => $rekap->kopi($filter),
            'jamur' => $rekap->jamur($filter),
            'perbandingan' => $rekap->perbandinganJamur($filter),
            'penjualan' => $rekap->penjualan($filter),
        ]);
    }
}
