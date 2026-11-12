<?php

namespace App\Http\Controllers\Admin\Profil;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\PotensiRequest;
use App\Models\Profil\Potensi;
use App\Services\Profil\PengelolaProfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PotensiController extends Controller
{
    public function __construct(private PengelolaProfil $pengelola) {}

    public function index(): View
    {
        return view('admin.profil.potensi.index', ['potensi' => Potensi::query()->with('fotos')->urut()->get()]);
    }

    public function create(): View
    {
        return view('admin.profil.potensi.form', ['potensi' => new Potensi(['kategori' => 'perkebunan'])]);
    }

    public function store(PotensiRequest $request): RedirectResponse
    {
        $potensi = $this->pengelola->simpanPotensi(new Potensi, $request->dataPotensi(), $request->fotoBaru());

        return to_route('admin.profil.potensi.index')->with('sukses', "{$potensi->nama} ditambahkan ke Potensi Desa.".$this->kabarGaleri($request));
    }

    public function edit(Potensi $potensi): View
    {
        return view('admin.profil.potensi.form', ['potensi' => $potensi->load('fotos')]);
    }

    public function update(PotensiRequest $request, Potensi $potensi): RedirectResponse
    {
        $this->pengelola->simpanPotensi($potensi, $request->dataPotensi(), $request->fotoBaru(), $request->hapusFoto(), $request->fotoUtama());

        return to_route('admin.profil.potensi.index')->with('sukses', "{$potensi->nama} diperbarui.".$this->kabarGaleri($request));
    }

    public function destroy(Potensi $potensi): RedirectResponse
    {
        $this->pengelola->hapusPotensi($potensi);

        return to_route('admin.profil.potensi.index')->with('sukses', "{$potensi->nama} dihapus dari Potensi Desa beserta fotonya (di Galeri juga).");
    }

    private function kabarGaleri(PotensiRequest $request): string
    {
        $jumlah = count($request->fotoBaru());

        return $jumlah > 0 ? " {$jumlah} foto baru juga masuk ke Galeri." : '';
    }
}
