<?php

namespace App\Http\Controllers\Admin\Profil;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\FotoRequest;
use App\Http\Requests\Profil\TambahFotoRequest;
use App\Models\Profil\Foto;
use App\Services\Profil\PengelolaGaleri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GaleriController extends Controller
{
    public function __construct(private PengelolaGaleri $pengelola) {}

    public function index(Request $request): View
    {
        $jenis = array_key_exists((string) $request->query('jenis'), Foto::JENIS) ? $request->query('jenis') : null;

        return view('admin.profil.galeri.index', [
            'jenisTerpilih' => $jenis,
            'foto' => Foto::query()->jenis($jenis)->with('berita:id,judul,slug,diterbitkan_pada')->terbaru()->paginate(12)->withQueryString(),
        ]);
    }

    // Hanya foto desa mandiri; foto kegiatan diunggah dari formulir berita.
    public function create(): View
    {
        return view('admin.profil.galeri.tambah');
    }

    public function store(TambahFotoRequest $request): RedirectResponse
    {
        $foto = $this->pengelola->tambah($request->berkas(), $request->dataFoto());

        return to_route('admin.profil.galeri.index')->with('sukses', "{$foto->count()} foto ditambahkan ke galeri.");
    }

    public function edit(Foto $foto): View
    {
        return view('admin.profil.galeri.ubah', ['foto' => $foto->load('berita')]);
    }

    public function update(FotoRequest $request, Foto $foto): RedirectResponse
    {
        $this->pengelola->ubah($foto, $request->dataFoto(), $request->file('foto'));

        return to_route('admin.profil.galeri.edit', $foto)->with('sukses', 'Foto diperbarui.');
    }

    public function destroy(Foto $foto): RedirectResponse
    {
        $this->pengelola->hapus($foto);

        // Bisa dihapus dari halaman galeri atau dari halaman ubah berita; kembali ke tempat asal.
        return back(fallback: route('admin.profil.galeri.index'))->with('sukses', 'Foto dihapus dari galeri.');
    }
}
