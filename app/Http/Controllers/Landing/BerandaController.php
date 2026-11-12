<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\Profil\Berita;
use App\Models\Profil\Foto;
use App\Services\Ews\PetaKerawanan;
use App\Services\Landing\RingkasanEws;
use App\Services\Profil\ProfilDesa;
use App\Support\Modul;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(RingkasanEws $ringkasan): View
    {
        $modul = Modul::semua();
        $adaBerita = $modul['berita']['tersedia'] ?? false;
        $adaGaleri = $modul['galeri']['tersedia'] ?? false;
        // 5 foto terbaru tampil lebih dulu; 20 foto berikutnya jadi cadangan yang digilir bergantian di halaman.
        $foto = $adaGaleri
            ? Foto::query()->publik()->with('berita:id,jenis,judul,slug,ringkasan,diterbitkan_pada')->terbaru()->limit(25)->get()
            : collect();

        return view('landing.index', [
            'modul' => $modul,
            'status' => $ringkasan->ambil(),
            // Kabar dan foto terbaru membuat halaman depan terasa hidup; tidak dimuat bila modulnya dimatikan.
            'beritaTerbaru' => $adaBerita
                ? Berita::query()->terbit()->withCount('foto')->terbaru()->limit(3)->get()
                : collect(),
            'pengumuman' => $adaBerita
                ? Berita::query()->terbit()->jenis('pengumuman')->terbaru()->limit(4)->get(['id', 'judul', 'slug', 'jenis', 'diterbitkan_pada'])
                : collect(),
            'fotoTerbaru' => $foto->take(5)->values(),
            'fotoGilir' => $foto->slice(5)->values(),
            'bentangAlam' => PetaKerawanan::bentangAlam(),
            'profil' => ($modul['profil']['tersedia'] ?? false) ? ProfilDesa::muat() : null,
        ]);
    }
}
