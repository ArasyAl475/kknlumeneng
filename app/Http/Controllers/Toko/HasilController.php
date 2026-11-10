<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\RekapRequest;
use App\Services\Toko\AnalisisHasil;
use App\Services\Toko\GoogleSheets;
use App\Services\Toko\RekapHasil;
use Illuminate\View\View;

class HasilController extends Controller
{
    public function index(RekapRequest $request, RekapHasil $rekap, AnalisisHasil $analisis, GoogleSheets $sheets): View
    {
        $filter = $request->validated();
        $periode = $rekap->periode($filter);
        $kopi = $rekap->kopi($filter);
        $jamur = $rekap->jamur($filter);
        $perbandingan = $rekap->perbandinganJamur($filter);
        $penjualan = $rekap->penjualan($filter);

        return view('toko.hasil', [
            'filter' => $filter,
            'periode' => $periode,
            'pilihan' => $rekap->pilihanKopi(),
            'kopi' => $kopi,
            'jamur' => $jamur,
            'perbandingan' => $perbandingan,
            'penjualan' => $penjualan,
            'penyaring' => $analisis->penyaringAktif($filter, $periode),
            'perubahan' => $analisis->perubahan($filter, $kopi, $jamur, $penjualan),
            'grafik' => $analisis->deret($filter),
            'grafikBaglog' => $analisis->baglogPerBulan($filter),
            'kesimpulan' => $analisis->kesimpulanBaglog($perbandingan),
            // Sheet yang sama dengan tombol di panel admin. null = sinkron Sheets belum dipasang; halaman memakai Unduh Excel.
            // Tanpa memilih tab supaya halaman publik tidak pernah menunggu Google.
            'alamatSheet' => $sheets->adaAlamat() ? $sheets->alamatWeb() : null,
        ]);
    }
}
