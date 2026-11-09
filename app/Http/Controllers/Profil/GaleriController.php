<?php

namespace App\Http\Controllers\Profil;

use App\Http\Controllers\Controller;
use App\Models\Profil\Foto;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GaleriController extends Controller
{
    private const KOLOM_BERITA = 'berita:id,jenis,judul,slug,ringkasan,gambar,diterbitkan_pada';

    public function index(Request $request): View
    {
        // Saringan yang tidak dikenal diabaikan saja, bukan halaman galat.
        $jenis = array_key_exists((string) $request->query('jenis'), Foto::JENIS) ? $request->query('jenis') : null;

        return view('profil.galeri.index', [
            'jenisTerpilih' => $jenis,
            'foto' => Foto::query()->publik()->jenis($jenis)->with(self::KOLOM_BERITA)->terbaru()->paginate(12)->withQueryString(),
        ]);
    }

    public function show(int $foto): View
    {
        // Foto dari berita draf menjawab 404, walau alamatnya tertebak.
        $foto = Foto::query()->publik()->with(self::KOLOM_BERITA)->findOrFail($foto);

        $lainnya = Foto::query()->publik()->whereKeyNot($foto->id)->with(self::KOLOM_BERITA)
            ->when($foto->terhubung(), fn ($q) => $q->where('berita_id', $foto->berita_id)->urut(), fn ($q) => $q->terbaru())
            ->limit(12)
            ->get();

        return view('profil.galeri.show', ['foto' => $foto, 'lainnya' => $lainnya]);
    }
}
