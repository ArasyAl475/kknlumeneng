<?php

namespace App\Http\Controllers\Edukasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Edukasi\DaftarMateriRequest;
use App\Models\Edukasi\Kategori;
use App\Models\Edukasi\Materi;
use Illuminate\View\View;

class MateriController extends Controller
{
    public function index(DaftarMateriRequest $request): View
    {
        $cari = $request->validated('cari');
        $kategori = $request->validated('kategori');

        return view('edukasi.index', [
            'cari' => $cari,
            'kategoriTerpilih' => $kategori,
            'kategori' => Kategori::query()->punyaMateriTerbit()->orderBy('nama')->get(['nama', 'slug', 'tips_sirkular']),
            'materi' => Materi::query()
                ->terbit()
                ->cari($cari)
                ->dalamKategori($kategori)
                ->with('kategori:id,nama')
                ->latest('diterbitkan_pada')
                ->latest('id')
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function show(string $slug): View
    {
        return view('edukasi.show', [
            // Draf menjawab 404, supaya isinya tidak terbaca walau alamatnya tertebak.
            'materi' => Materi::query()->terbit()->where('slug', $slug)->with(['kategori:id,nama', 'langkah'])->firstOrFail(),
            'pratinjau' => false,
        ]);
    }
}
