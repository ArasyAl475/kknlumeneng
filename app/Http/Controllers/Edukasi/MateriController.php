<?php

namespace App\Http\Controllers\Edukasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Edukasi\DaftarMateriRequest;
use App\Models\Edukasi\Kategori;
use App\Models\Edukasi\Materi;
use App\Services\Edukasi\BagianKategori;
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
                // Satu kategori dibaca berurutan seperti bab; tampilan "Semua" menampilkan yang terbaru dulu.
                ->when(
                    filled($kategori),
                    fn ($q) => $q->urutBagian(),
                    fn ($q) => $q->latest('diterbitkan_pada')->latest('id'),
                )
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function show(string $slug): View
    {
        // Draf menjawab 404, supaya isinya tidak terbaca walau alamatnya tertebak.
        $materi = Materi::query()->terbit()->where('slug', $slug)->with(['kategori:id,nama,slug', 'langkah'])->firstOrFail();

        return view('edukasi.show', ['materi' => $materi, 'pratinjau' => false, 'bagian' => BagianKategori::untuk($materi)]);
    }
}
