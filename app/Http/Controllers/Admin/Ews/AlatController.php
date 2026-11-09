<?php

namespace App\Http\Controllers\Admin\Ews;

use App\Http\Controllers\Controller;
use App\Models\Ews\Wilayah;
use App\Services\Ews\PenilaianStatus;
use Illuminate\View\View;

class AlatController extends Controller
{
    public function __invoke(): View
    {
        $batas = (int) config('ews.alat_offline_menit');

        $alat = Wilayah::query()->with('pembacaanTerbaru')->orderBy('id')->get()
            ->map(fn (Wilayah $w) => [
                'id' => $w->id,
                'nama' => $w->nama,
                'terakhir' => $w->pembacaanTerbaru?->created_at,
                'offline' => PenilaianStatus::alatOffline($w->pembacaanTerbaru?->created_at, $batas),
            ]);

        return view('admin.ews.alat', [
            'alat' => $alat,
            'batasMenit' => $batas,
            // Hanya ada/tidaknya yang diperlihatkan, nilainya tidak pernah dikirim ke tampilan.
            'adaKunciApi' => filled(config('ews.api_key')),
        ]);
    }
}
