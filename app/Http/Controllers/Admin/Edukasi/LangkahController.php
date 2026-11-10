<?php

namespace App\Http\Controllers\Admin\Edukasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Edukasi\LangkahRequest;
use App\Http\Requests\Edukasi\PindahLangkahRequest;
use App\Models\Edukasi\Langkah;
use App\Models\Edukasi\Materi;
use App\Services\Edukasi\PengelolaMateri;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LangkahController extends Controller
{
    public function __construct(private PengelolaMateri $pengelola) {}

    public function create(Materi $materi): View
    {
        return view('admin.edukasi.langkah.form', ['materi' => $materi, 'langkah' => new Langkah]);
    }

    public function store(LangkahRequest $request, Materi $materi): RedirectResponse
    {
        $this->pengelola->tambahLangkah($materi, $request->dataLangkah(), $request->file('gambar'));

        return $this->keMateri($materi->id, 'Langkah ditambahkan.');
    }

    public function edit(Langkah $langkah): View
    {
        return view('admin.edukasi.langkah.form', ['materi' => $langkah->materi, 'langkah' => $langkah]);
    }

    public function update(LangkahRequest $request, Langkah $langkah): RedirectResponse
    {
        $this->pengelola->ubah($langkah, $request->dataLangkah(), $request->file('gambar'), $request->boolean('hapus_gambar'));

        return $this->keMateri($langkah->materi_id, 'Langkah diperbarui.');
    }

    public function destroy(Langkah $langkah): RedirectResponse
    {
        $this->pengelola->hapusLangkah($langkah);

        return $this->keMateri($langkah->materi_id, 'Langkah dihapus.');
    }

    public function pindah(PindahLangkahRequest $request, Langkah $langkah): RedirectResponse
    {
        $this->pengelola->pindahLangkah($langkah, $request->validated('arah'));

        return $this->keMateri($langkah->materi_id, 'Urutan langkah diperbarui.');
    }

    private function keMateri(int $materiId, string $pesan): RedirectResponse
    {
        // Jangkar #langkah agar admin tetap melihat daftar langkah, tidak terlempar ke atas halaman.
        return redirect(route('admin.edukasi.materi.edit', $materiId).'#langkah')->with('sukses', $pesan);
    }
}
