<?php

namespace App\Http\Controllers\Admin\Edukasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Edukasi\MateriRequest;
use App\Models\Edukasi\Kategori;
use App\Models\Edukasi\Materi;
use App\Services\Edukasi\BagianKategori;
use App\Services\Edukasi\PengelolaMateri;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MateriController extends Controller
{
    public function __construct(private PengelolaMateri $pengelola) {}

    public function index(): View
    {
        return view('admin.edukasi.materi.index', [
            'materi' => Materi::query()->with('kategori:id,nama')->withCount('langkah')->latest('updated_at')->latest('id')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('admin.edukasi.materi.form', ['materi' => new Materi, 'kategori' => $this->pilihanKategori()]);
    }

    public function store(MateriRequest $request): RedirectResponse
    {
        $materi = $this->pengelola->buatMateri($request->dataMateri(), $request->file('gambar'));

        // Langkah hanya bisa ditambahkan setelah materinya ada, jadi admin langsung dibawa ke halaman ubah.
        return to_route('admin.edukasi.materi.edit', $materi)->with('sukses', 'Materi tersimpan sebagai draf. Warga belum bisa melihatnya.');
    }

    public function edit(Materi $materi): View
    {
        return view('admin.edukasi.materi.form', ['materi' => $materi->load('langkah'), 'kategori' => $this->pilihanKategori()]);
    }

    public function update(MateriRequest $request, Materi $materi): RedirectResponse
    {
        $this->pengelola->ubah($materi, $request->dataMateri(), $request->file('gambar'), $request->boolean('hapus_gambar'));

        return to_route('admin.edukasi.materi.edit', $materi)->with('sukses', 'Materi diperbarui.');
    }

    public function destroy(Materi $materi): RedirectResponse
    {
        $this->pengelola->hapusMateri($materi);

        return to_route('admin.edukasi.materi.index')->with('sukses', "Materi {$materi->judul} dihapus.");
    }

    public function pratinjau(Materi $materi): View
    {
        $materi->load(['kategori:id,nama,slug', 'langkah']);

        return view('edukasi.show', ['materi' => $materi, 'pratinjau' => true, 'bagian' => BagianKategori::untuk($materi)]);
    }

    public function terbitkan(Materi $materi): RedirectResponse
    {
        if (! $materi->siapTerbit()) {
            return back()->with('galat', 'Materi belum bisa diterbitkan. Isi penjelasan atau tambahkan minimal satu langkah dulu.');
        }

        $this->pengelola->terbitkan($materi);

        return back()->with('sukses', "Materi {$materi->judul} sudah terbit dan bisa dibaca warga.");
    }

    public function tarik(Materi $materi): RedirectResponse
    {
        $this->pengelola->tarik($materi);

        return back()->with('sukses', "Materi {$materi->judul} menjadi draf dan tidak lagi terlihat warga.");
    }

    private function pilihanKategori()
    {
        return Kategori::query()->orderBy('nama')->get(['id', 'nama']);
    }
}
