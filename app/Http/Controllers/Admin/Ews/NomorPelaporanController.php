<?php

namespace App\Http\Controllers\Admin\Ews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ews\NomorPelaporanRequest;
use App\Models\Ews\Aktivitas;
use App\Services\Ews\NomorPelaporan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NomorPelaporanController extends Controller
{
    public function edit(): View
    {
        return view('admin.ews.pelaporan', ['pelaporan' => NomorPelaporan::data()]);
    }

    public function update(NomorPelaporanRequest $request): RedirectResponse
    {
        $lama = NomorPelaporan::ambil();
        $baru = $request->validated('nomor_wa');

        if ($baru !== $lama) {
            NomorPelaporan::simpan($baru);
            Aktivitas::catat($request->user(), 'ubah_nomor_pelaporan', 'Mengubah nomor WhatsApp pelaporan: '
                .($lama ? NomorPelaporan::tampil($lama) : 'belum ada').' menjadi '.NomorPelaporan::tampil($baru));
        }

        return back()->with('sukses', 'Nomor pelaporan disimpan. Aplikasi HP memakai nomor baru paling lambat 1 menit lagi.');
    }
}
