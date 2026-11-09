<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desain\SlotGambar;
use App\Models\Edukasi\Materi;
use App\Models\Ews\Pembacaan;
use App\Models\Profil\Berita;
use App\Models\Profil\Foto;
use App\Models\Toko\Produk;
use App\Services\Landing\RingkasanEws;
use App\Support\Modul;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(RingkasanEws $ringkasan): View
    {
        $modul = Modul::semua();
        $ada = fn (string $kunci) => $modul[$kunci]['tersedia'] ?? false;

        // Angka ringkas per modul. Modul yang dimatikan tidak ikut dihitung (null = kartu tidak tampil).
        $angka = [
            'menunggu' => $ada('ews') ? Pembacaan::query()->where('validasi', Pembacaan::VALIDASI_MENUNGGU)->count() : null,
            'berita' => $ada('berita') ? Berita::query()->terbit()->count() : null,
            'draf' => $ada('berita') ? Berita::query()->whereNull('diterbitkan_pada')->count() : null,
            'foto' => $ada('galeri') ? Foto::query()->count() : null,
            'produk' => $ada('toko') ? Produk::query()->tampil()->count() : null,
            'materi' => $ada('edukasi') ? Materi::query()->terbit()->count() : null,
            'slot' => SlotGambar::query()->whereNotNull('path')->count(),
        ];

        return view('admin.beranda', [
            'modul' => $modul,
            'status' => $ada('ews') ? $ringkasan->ambil() : null,
            'angka' => $angka,
        ]);
    }
}
