<?php

namespace App\Http\Controllers\Admin\Desain;

use App\Http\Controllers\Controller;
use App\Http\Requests\Desain\SlotGambarRequest;
use App\Models\Desain\SlotGambar;
use App\Services\Desain\DaftarSlot;
use App\Services\Desain\GambarSitus;
use App\Services\Desain\PenyimpanGambarSitus;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SlotGambarController extends Controller
{
    public function __construct(
        private PenyimpanGambarSitus $penyimpan,
        private GambarSitus $gambar,
    ) {}

    public function index(): View
    {
        // Menu tetap lengkap walau seeder lupa dijalankan setelah slot baru ditambahkan di kode.
        DaftarSlot::sinkronkan();
        $this->gambar->lupakan();

        return view('admin.desain.index', [
            'kelompok' => SlotGambar::query()->orderBy('urutan')->get()->groupBy('halaman'),
            'batas' => PenyimpanGambarSitus::teksBatas(),
            'maksKb' => PenyimpanGambarSitus::maksimalKb(),
        ]);
    }

    public function update(SlotGambarRequest $request, SlotGambar $slot): RedirectResponse
    {
        $lama = $slot->path;

        if ($berkas = $request->file('gambar')) {
            $slot->path = $this->penyimpan->simpan($berkas, 'gambar');
        }

        $slot->alt = trim((string) $request->validated('alt'));
        $slot->save();

        // Berkas lama dihapus setelah data tersimpan, agar situs tidak sempat menunjuk berkas yang hilang.
        if ($lama !== $slot->path) {
            $this->penyimpan->hapus($lama);
        }

        $this->gambar->lupakan();

        return redirect()->to(route('admin.desain.index').'#slot-'.$slot->id)
            ->with('sukses', "{$slot->nama} sudah disimpan dan langsung tampil di situs.");
    }

    public function pulihkan(SlotGambar $slot): RedirectResponse
    {
        $lama = $slot->path;

        $slot->forceFill(['path' => null, 'alt' => DaftarSlot::ambil($slot->kunci)['alt'] ?? ''])->save();
        $this->penyimpan->hapus($lama);
        $this->gambar->lupakan();

        return redirect()->to(route('admin.desain.index').'#slot-'.$slot->id)
            ->with('sukses', "{$slot->nama} kembali memakai gambar bawaan.");
    }
}
