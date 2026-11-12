<?php

namespace App\Http\Controllers\Admin\Ews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ews\WilayahRequest;
use App\Models\Ews\Aktivitas;
use App\Models\Ews\Pembacaan;
use App\Models\Ews\Pengaturan;
use App\Models\Ews\Titik;
use App\Models\Ews\Wilayah;
use App\Services\Ews\DataDasbor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WilayahController extends Controller
{
    public function index(): View
    {
        return view('admin.ews.wilayah.index', [
            'ambang' => Pengaturan::ambang(),
            'wilayah' => Wilayah::query()->with('pembacaanTerbaru')->orderBy('id')->get(),
            'terhapus' => Wilayah::onlyTrashed()->orderByDesc('deleted_at')->get(),
            'titik' => Titik::query()->orderBy('jenis')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.ews.wilayah.form', [
            'wilayah' => new Wilayah,
            'lainnya' => $this->poligonLain(),
            'idAlatSaran' => Wilayah::idAlatBebas(),
        ]);
    }

    public function store(WilayahRequest $request): RedirectResponse
    {
        $wilayah = Wilayah::query()->create($request->dataWilayah());
        Aktivitas::catat($request->user(), 'tambah_wilayah', "Menambah wilayah {$wilayah->nama} (ID alat {$wilayah->id_alat}).");
        DataDasbor::lupakan();

        return to_route('admin.ews.wilayah.index')->with('sukses', "Wilayah {$wilayah->nama} ditambahkan. ID alat: {$wilayah->id_alat}.");
    }

    public function edit(Wilayah $wilayah): View
    {
        return view('admin.ews.wilayah.form', [
            'wilayah' => $wilayah,
            'lainnya' => $this->poligonLain($wilayah),
            'idAlatSaran' => $wilayah->id_alat,
        ]);
    }

    public function update(WilayahRequest $request, Wilayah $wilayah): RedirectResponse
    {
        $namaLama = $wilayah->nama;
        $idAlatLama = $wilayah->id_alat;
        $wilayah->update($request->dataWilayah());
        Aktivitas::catat($request->user(), 'ubah_wilayah', $namaLama === $wilayah->nama ? "Mengubah batas wilayah {$wilayah->nama}." : "Mengubah wilayah {$namaLama} menjadi {$wilayah->nama}.");
        $pesan = "Wilayah {$wilayah->nama} diperbarui.";

        if ($idAlatLama !== $wilayah->id_alat) {
            Aktivitas::catat($request->user(), 'ubah_id_alat', "Mengganti ID alat wilayah {$wilayah->nama} dari {$idAlatLama} menjadi {$wilayah->id_alat}.");
            $pesan .= " ID alat sekarang {$wilayah->id_alat}: samakan WILAYAH_ID di ESP32 (dan topik MQTT) dengan angka ini.";
        }
        DataDasbor::lupakan();

        return to_route('admin.ews.wilayah.index')->with('sukses', $pesan);
    }

    public function destroy(Request $request, Wilayah $wilayah): RedirectResponse
    {
        // Menghapus wilayah Awas akan menghilangkan peringatan aktif dari dashboard warga.
        if ($wilayah->status_awas) {
            return back()->with('galat', "{$wilayah->nama} masih berstatus Awas. Cabut dulu statusnya sebelum menghapus.");
        }

        $idAlatLama = $wilayah->id_alat;

        DB::transaction(function () use ($request, $wilayah, $idAlatLama) {
            // Laporan yang menunggu tidak bisa lagi diputuskan setelah wilayahnya hilang dari daftar.
            $wilayah->pembacaan()->where('validasi', Pembacaan::VALIDASI_MENUNGGU)->update([
                'validasi' => Pembacaan::VALIDASI_DITOLAK,
                'divalidasi_pada' => now(),
                'divalidasi_oleh' => $request->user()->id,
            ]);
            $wilayah->delete();
            Aktivitas::catat($request->user(), 'hapus_wilayah', "Menghapus wilayah {$wilayah->nama} (ID alat {$idAlatLama}).");
        });
        DataDasbor::lupakan();

        return back()->with('sukses', "Wilayah {$wilayah->nama} dihapus. Riwayat datanya tetap tersimpan.");
    }

    public function pulihkan(Request $request, int $id): RedirectResponse
    {
        $wilayah = Wilayah::onlyTrashed()->findOrFail($id);
        $wilayah->restore();
        Aktivitas::catat($request->user(), 'pulihkan_wilayah', "Memulihkan wilayah {$wilayah->nama} (ID alat {$wilayah->id_alat}).");
        DataDasbor::lupakan();

        return back()->with('sukses', "Wilayah {$wilayah->nama} dipulihkan dengan ID alat {$wilayah->id_alat}. Ubah angkanya di halaman Ubah bila alat memakai ID lain.");
    }

    /**
     * @return array<int, array{nama: string, poligon: array}>
     */
    private function poligonLain(?Wilayah $kecuali = null): array
    {
        $titik = Titik::query()->whereNotNull('poligon')->get(['nama', 'poligon']);

        return Wilayah::query()
            ->when($kecuali, fn ($q) => $q->whereKeyNot($kecuali->id))
            ->get(['nama', 'poligon'])
            ->concat($titik)
            ->map(fn ($t) => ['nama' => $t->nama, 'poligon' => $t->poligon])
            ->all();
    }
}
