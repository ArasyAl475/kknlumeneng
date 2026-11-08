<?php

namespace App\Http\Controllers\Admin\Edukasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Edukasi\KategoriRequest;
use App\Models\Edukasi\Kategori;
use App\Services\Edukasi\PembuatSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KategoriController extends Controller
{
    public function index(): View
    {
        return view('admin.edukasi.kategori.index', [
            'kategori' => Kategori::query()->withCount('materi')->orderBy('nama')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.edukasi.kategori.form', ['kategori' => new Kategori]);
    }

    public function store(KategoriRequest $request): RedirectResponse
    {
        $nama = $request->validated('nama');
        Kategori::query()->create([
            'nama' => $nama,
            'slug' => PembuatSlug::untuk(Kategori::class, $nama, 'kategori'),
            'tips_sirkular' => $request->validated('tips_sirkular'),
        ]);

        return to_route('admin.edukasi.kategori.index')->with('sukses', "Kategori {$nama} ditambahkan.");
    }

    public function edit(Kategori $kategori): View
    {
        return view('admin.edukasi.kategori.form', ['kategori' => $kategori]);
    }

    public function update(KategoriRequest $request, Kategori $kategori): RedirectResponse
    {
        $kategori->update($request->safe()->only(['nama', 'tips_sirkular']));

        return to_route('admin.edukasi.kategori.index')->with('sukses', "Kategori {$kategori->nama} diperbarui.");
    }

    public function destroy(Kategori $kategori): RedirectResponse
    {
        if ($kategori->materi()->exists()) {
            return back()->with('galat', "Kategori {$kategori->nama} masih berisi materi. Pindahkan atau hapus dulu materinya.");
        }

        $kategori->delete();

        return back()->with('sukses', "Kategori {$kategori->nama} dihapus.");
    }
}
