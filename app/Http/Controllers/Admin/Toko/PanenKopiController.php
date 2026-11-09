<?php

namespace App\Http\Controllers\Admin\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\PanenKopiRequest;
use App\Models\Toko\PanenKopi;
use App\Services\Toko\RekapHasil;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PanenKopiController extends Controller
{
    public function index(RekapHasil $rekap): View
    {
        return view('admin.toko.panen-kopi.index', [
            'ringkasan' => $rekap->kopi(),
            'panen' => PanenKopi::query()->orderByDesc('tanggal_panen')->orderByDesc('id')->paginate(10),
        ]);
    }

    public function create(RekapHasil $rekap): View
    {
        return view('admin.toko.panen-kopi.form', ['panen' => new PanenKopi, 'pilihan' => $rekap->pilihanKopi()]);
    }

    public function store(PanenKopiRequest $request): RedirectResponse
    {
        PanenKopi::query()->create($request->validated());

        return to_route('admin.toko.panen-kopi.index')->with('sukses', 'Panen kopi dicatat.');
    }

    public function edit(PanenKopi $panenKopi, RekapHasil $rekap): View
    {
        return view('admin.toko.panen-kopi.form', ['panen' => $panenKopi, 'pilihan' => $rekap->pilihanKopi()]);
    }

    public function update(PanenKopiRequest $request, PanenKopi $panenKopi): RedirectResponse
    {
        $panenKopi->update($request->validated());

        return to_route('admin.toko.panen-kopi.index')->with('sukses', 'Catatan panen kopi diperbarui.');
    }

    public function destroy(PanenKopi $panenKopi): RedirectResponse
    {
        $panenKopi->delete();

        return back()->with('sukses', 'Catatan panen kopi dihapus.');
    }
}
