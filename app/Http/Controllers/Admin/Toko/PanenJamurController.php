<?php

namespace App\Http\Controllers\Admin\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\PanenJamurRequest;
use App\Models\Toko\PanenJamur;
use App\Services\Toko\RekapHasil;
use App\Services\Toko\VersiHasil;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PanenJamurController extends Controller
{
    public function index(RekapHasil $rekap, VersiHasil $versi): View
    {
        return view('admin.toko.panen-jamur.index', [
            // Paling awal, sebelum data diambil (lihat toko/partials/penanda-segar).
            'versi' => $versi->sekarang(),
            'ringkasan' => $rekap->jamur(),
            'keuangan' => $rekap->keuanganJamur(),
            'panen' => PanenJamur::query()->orderByDesc('tanggal_panen')->orderByDesc('id')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('admin.toko.panen-jamur.form', ['panen' => new PanenJamur]);
    }

    public function store(PanenJamurRequest $request): RedirectResponse
    {
        PanenJamur::query()->create($request->validated());

        return to_route('admin.toko.panen-jamur.index')->with('sukses', 'Panen jamur dicatat.');
    }

    public function edit(PanenJamur $panenJamur): View
    {
        return view('admin.toko.panen-jamur.form', ['panen' => $panenJamur]);
    }

    public function update(PanenJamurRequest $request, PanenJamur $panenJamur): RedirectResponse
    {
        $panenJamur->update($request->validated());

        return to_route('admin.toko.panen-jamur.index')->with('sukses', 'Catatan panen jamur diperbarui.');
    }

    public function destroy(PanenJamur $panenJamur): RedirectResponse
    {
        $panenJamur->delete();

        return back()->with('sukses', 'Catatan panen jamur dihapus.');
    }
}
