<?php

namespace App\Http\Controllers\Admin\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\PengaturanRequest;
use App\Models\Toko\Aktivitas;
use App\Models\Toko\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function edit(): View
    {
        return view('admin.toko.pengaturan', ['pengaturan' => Pengaturan::semua()]);
    }

    public function update(PengaturanRequest $request): RedirectResponse
    {
        Pengaturan::simpan($request->validated());
        Aktivitas::catat($request->user(), 'ubah_pengaturan', 'Mengubah info penjual dan lokasi pengiriman.');

        return to_route('admin.toko.pengaturan.edit')->with('sukses', 'Pengaturan toko disimpan.');
    }
}
