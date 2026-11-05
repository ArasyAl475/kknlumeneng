<?php

namespace App\Http\Controllers\Admin\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\PenjualanJamurRequest;
use App\Models\Toko\PenjualanJamur;
use App\Services\Toko\RekapHasil;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PenjualanJamurController extends Controller
{
    public function index(RekapHasil $rekap): View
    {
        return view('admin.toko.penjualan-jamur.index', [
            'ringkasan' => $rekap->penjualan(),
            'keuangan' => $rekap->keuanganJamur(),
            'penjualan' => PenjualanJamur::query()->orderByDesc('tanggal_penjualan')->orderByDesc('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.toko.penjualan-jamur.form', ['penjualan' => new PenjualanJamur]);
    }

    public function store(PenjualanJamurRequest $request): RedirectResponse
    {
        PenjualanJamur::query()->create($request->validated());

        return to_route('admin.toko.penjualan-jamur.index')->with('sukses', 'Penjualan jamur dicatat.');
    }

    public function edit(PenjualanJamur $penjualanJamur): View
    {
        return view('admin.toko.penjualan-jamur.form', ['penjualan' => $penjualanJamur]);
    }

    public function update(PenjualanJamurRequest $request, PenjualanJamur $penjualanJamur): RedirectResponse
    {
        $penjualanJamur->update($request->validated());

        return to_route('admin.toko.penjualan-jamur.index')->with('sukses', 'Catatan penjualan jamur diperbarui.');
    }

    public function destroy(PenjualanJamur $penjualanJamur): RedirectResponse
    {
        $penjualanJamur->delete();

        return back()->with('sukses', 'Catatan penjualan jamur dihapus.');
    }
}
