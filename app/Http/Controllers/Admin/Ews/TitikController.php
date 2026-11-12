<?php

namespace App\Http\Controllers\Admin\Ews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ews\TitikRequest;
use App\Models\Ews\Aktivitas;
use App\Models\Ews\Titik;
use App\Models\Ews\Wilayah;
use App\Services\Ews\DataDasbor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TitikController extends Controller
{
    public function create(): View
    {
        return view('admin.ews.titik.form', ['titik' => new Titik(['jenis' => Titik::JENIS_RAWAN]), 'lainnya' => $this->poligonLain()]);
    }

    public function store(TitikRequest $request): RedirectResponse
    {
        $titik = Titik::query()->create($request->dataTitik());
        Aktivitas::catat($request->user(), 'tambah_titik', "Menambah titik {$titik->nama} ({$titik->namaJenis()}).");
        DataDasbor::lupakan();

        return to_route('admin.ews.wilayah.index')->with('sukses', "Titik {$titik->nama} ditambahkan ke peta.");
    }

    public function edit(Titik $titik): View
    {
        return view('admin.ews.titik.form', ['titik' => $titik, 'lainnya' => $this->poligonLain($titik)]);
    }

    public function update(TitikRequest $request, Titik $titik): RedirectResponse
    {
        $namaLama = $titik->nama;
        $titik->update($request->dataTitik());
        Aktivitas::catat($request->user(), 'ubah_titik', $namaLama === $titik->nama ? "Mengubah titik {$titik->nama}." : "Mengubah titik {$namaLama} menjadi {$titik->nama}.");
        DataDasbor::lupakan();

        return to_route('admin.ews.wilayah.index')->with('sukses', "Titik {$titik->nama} diperbarui.");
    }

    public function destroy(Request $request, Titik $titik): RedirectResponse
    {
        // Titik tidak punya riwayat sensor, jadi dihapus sungguhan; kalau salah hapus, tambahkan lagi.
        $titik->delete();
        Aktivitas::catat($request->user(), 'hapus_titik', "Menghapus titik {$titik->nama} ({$titik->namaJenis()}).");
        DataDasbor::lupakan();

        return back()->with('sukses', "Titik {$titik->nama} dihapus dari peta.");
    }

    /**
     * Batas daerah lain sebagai latar di peta form, supaya admin tidak menggambar tumpang tindih.
     *
     * @return array<int, array{nama: string, poligon: array}>
     */
    private function poligonLain(?Titik $kecuali = null): array
    {
        $titik = Titik::query()->whereNotNull('poligon')
            ->when($kecuali, fn ($q) => $q->whereKeyNot($kecuali->id))
            ->get(['nama', 'poligon']);

        return Wilayah::query()->get(['nama', 'poligon'])->concat($titik)
            ->map(fn ($t) => ['nama' => $t->nama, 'poligon' => $t->poligon])
            ->all();
    }
}
