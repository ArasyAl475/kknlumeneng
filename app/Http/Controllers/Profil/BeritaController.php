<?php

namespace App\Http\Controllers\Profil;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\DaftarBeritaRequest;
use App\Models\Profil\Berita;
use Illuminate\View\View;

class BeritaController extends Controller
{
    public function index(DaftarBeritaRequest $request): View
    {
        $cari = $request->validated('cari');
        $jenis = $request->validated('jenis');

        return view('profil.berita.index', [
            'cari' => $cari,
            'jenisTerpilih' => $jenis,
            'berita' => Berita::query()->terbit()->jenis($jenis)->cari($cari)->withCount('foto')->terbaru()->paginate(9)->withQueryString(),
        ]);
    }

    public function show(string $slug): View
    {
        // Draf menjawab 404, supaya isinya tidak terbaca walau alamatnya tertebak.
        $berita = Berita::query()->terbit()->where('slug', $slug)->with('foto')->firstOrFail();

        return view('profil.berita.show', [
            'berita' => $berita,
            'lainnya' => $this->lainnya($berita),
            'pratinjau' => false,
        ]);
    }

    private function lainnya(Berita $berita)
    {
        return Berita::query()->terbit()->whereKeyNot($berita->getKey())->withCount('foto')->terbaru()->limit(3)->get();
    }
}
