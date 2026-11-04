<?php

namespace App\Http\Controllers\Admin\Ews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ews\SimulasiRequest;
use App\Models\Ews\Pembacaan;
use App\Models\Ews\Pengaturan;
use App\Models\Ews\Wilayah;
use App\Services\Ews\PenerimaPembacaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SimulatorController extends Controller
{
    public function __construct()
    {
        // Lapis kedua selain rute yang tidak didaftarkan di produksi: data buatan
        // tidak boleh mungkin disangka kondisi lereng sungguhan.
        abort_if(app()->isProduction(), 404);
    }

    public function index(): View
    {
        return view('admin.ews.simulator', [
            'wilayah' => Wilayah::query()->orderBy('id')->get(['id', 'nama']),
            'ambang' => Pengaturan::ambang(),
        ]);
    }

    public function kirim(SimulasiRequest $request, PenerimaPembacaan $penerima): RedirectResponse
    {
        $bacaan = $request->bacaan();
        $kabel = $request->boolean('kabel_putus');

        if (array_filter($bacaan, fn ($n) => $n !== null) === [] && ! $kabel) {
            return back()->withInput()->with('galat', 'Isi minimal satu sensor atau centang kabel putus.');
        }

        $wilayah = Wilayah::query()->findOrFail($request->integer('wilayah_id'));
        $p = $penerima->terima($wilayah, $bacaan, $kabel);

        $pesan = "Data tersimpan: {$wilayah->nama} ({$p->status}).";
        $pesan .= $p->pemicu ? " Pemicu: {$p->pemicu}." : '';
        $pesan .= $p->validasi === Pembacaan::VALIDASI_MENUNGGU ? ' Laporan masuk antrean validasi.' : '';

        return back()->withInput()->with('sukses', $pesan);
    }
}
