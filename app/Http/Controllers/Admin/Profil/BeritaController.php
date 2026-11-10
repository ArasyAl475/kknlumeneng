<?php

namespace App\Http\Controllers\Admin\Profil;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\BeritaRequest;
use App\Models\Profil\Berita;
use App\Services\Profil\PengelolaBerita;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BeritaController extends Controller
{
    public function __construct(private PengelolaBerita $pengelola) {}

    public function index(Request $request): View
    {
        $jenis = array_key_exists((string) $request->query('jenis'), Berita::JENIS) ? $request->query('jenis') : null;

        return view('admin.profil.berita.index', [
            'jenisTerpilih' => $jenis,
            // Draf (tanggal terbit kosong) tampil paling atas agar tidak terlupa.
            'berita' => Berita::query()->jenis($jenis)->orderByRaw('diterbitkan_pada is null desc')->terbaru()->paginate(10)->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.profil.berita.form', [
            'berita' => new Berita(['jenis' => 'berita', 'penulis' => $request->user()->name]),
        ]);
    }

    public function store(BeritaRequest $request): RedirectResponse
    {
        $berita = $this->pengelola->buat($request->dataBerita(), $request->file('gambar'), $request->terbit(), $request->fotoKegiatan());

        return to_route('admin.profil.berita.index')->with('sukses', $berita->sudahTerbit()
            ? "\"{$berita->judul}\" sudah terbit dan bisa dibaca warga."
            : "\"{$berita->judul}\" tersimpan sebagai draf. Warga belum bisa melihatnya.");
    }

    public function edit(Berita $berita): View
    {
        return view('admin.profil.berita.form', ['berita' => $berita->load('foto')]);
    }

    public function update(BeritaRequest $request, Berita $berita): RedirectResponse
    {
        $this->pengelola->ubah($berita, $request->dataBerita(), $request->file('gambar'), $request->boolean('hapus_gambar'), $request->terbit(), $request->fotoKegiatan());

        return to_route('admin.profil.berita.edit', $berita)->with('sukses', $berita->sudahTerbit()
            ? 'Perubahan tersimpan dan langsung tampil di situs.'
            : 'Perubahan tersimpan sebagai draf. Warga belum bisa melihatnya.');
    }

    public function destroy(Berita $berita): RedirectResponse
    {
        $this->pengelola->hapus($berita);

        return to_route('admin.profil.berita.index')->with('sukses', "\"{$berita->judul}\" dihapus.");
    }

    public function pratinjau(Berita $berita): View
    {
        return view('profil.berita.show', ['berita' => $berita->load('foto'), 'lainnya' => collect(), 'pratinjau' => true]);
    }
}
