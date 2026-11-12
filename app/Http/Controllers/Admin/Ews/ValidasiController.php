<?php

namespace App\Http\Controllers\Admin\Ews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ews\CabutAwasRequest;
use App\Http\Requests\Ews\NyatakanAwasRequest;
use App\Models\Ews\Pembacaan;
use App\Models\Ews\Pengaturan;
use App\Models\Ews\Wilayah;
use App\Services\Ews\DataDasbor;
use App\Services\Ews\KeputusanAdmin;
use App\Services\Ews\PenilaianStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ValidasiController extends Controller
{
    public function index(DataDasbor $dasbor): View
    {
        $ambang = Pengaturan::ambang();

        // Wilayah yang belum Awas, yang alatnya diam saat Waspada ditaruh paling atas: admin perlu memutuskan.
        $bisaDinyatakan = collect($dasbor->ambil()['wilayah'])
            ->reject(fn (array $w) => $w['status'] === PenilaianStatus::AWAS)
            ->sortBy([['kondisi_tak_diketahui', 'desc'], ['id', 'asc']])
            ->values();

        $laporan = Pembacaan::query()->with('wilayah')
            ->where('validasi', Pembacaan::VALIDASI_MENUNGGU)
            ->latest('id')
            ->get()
            ->each(fn (Pembacaan $p) => $p->setAttribute('level_sensor', PenilaianStatus::levelSensor($p->only(PenilaianStatus::SENSOR_DINILAI), $ambang)));

        return view('admin.ews.validasi', [
            'laporan' => $laporan,
            'wilayahAwas' => Wilayah::query()->where('status_awas', true)->orderBy('awas_sejak')->get(),
            'bisaDinyatakan' => $bisaDinyatakan,
        ]);
    }

    public function formNyatakan(Wilayah $wilayah): View|RedirectResponse
    {
        if ($wilayah->status_awas) {
            return to_route('admin.ews.validasi.index')->with('galat', "{$wilayah->nama} sudah berstatus Awas.");
        }

        return view('admin.ews.nyatakan', ['wilayah' => $wilayah]);
    }

    public function nyatakan(NyatakanAwasRequest $request, Wilayah $wilayah, KeputusanAdmin $keputusan): RedirectResponse
    {
        return $keputusan->nyatakanAwas($wilayah, $request->user(), $request->validated('alasan'))
            ? to_route('admin.ews.validasi.index')->with('sukses', "{$wilayah->nama} sekarang berstatus Awas. Warga langsung melihatnya di dashboard.")
            : to_route('admin.ews.validasi.index')->with('galat', "{$wilayah->nama} sudah berstatus Awas.");
    }

    public function setujui(Request $request, Pembacaan $pembacaan, KeputusanAdmin $keputusan): RedirectResponse
    {
        $pembacaan->load('wilayah');

        return $keputusan->setujui($pembacaan, $request->user())
            ? back()->with('sukses', "Laporan disetujui. {$pembacaan->wilayah->nama} sekarang berstatus Awas.")
            : back()->with('galat', 'Laporan ini sudah diputuskan sebelumnya.');
    }

    public function tolak(Request $request, Pembacaan $pembacaan, KeputusanAdmin $keputusan): RedirectResponse
    {
        $pembacaan->load('wilayah');

        return $keputusan->tolak($pembacaan, $request->user())
            ? back()->with('sukses', 'Laporan ditolak. Status wilayah tidak berubah.')
            : back()->with('galat', 'Laporan ini sudah diputuskan sebelumnya.');
    }

    public function konfirmasiCabut(Wilayah $wilayah): View|RedirectResponse
    {
        if (! $wilayah->status_awas) {
            return to_route('admin.ews.validasi.index')->with('galat', "{$wilayah->nama} sedang tidak berstatus Awas.");
        }

        return view('admin.ews.cabut', ['wilayah' => $wilayah]);
    }

    public function cabut(CabutAwasRequest $request, Wilayah $wilayah, KeputusanAdmin $keputusan): RedirectResponse
    {
        if (! $wilayah->status_awas) {
            return to_route('admin.ews.validasi.index')->with('galat', "{$wilayah->nama} sedang tidak berstatus Awas.");
        }

        $keputusan->cabutAwas($wilayah, $request->user(), $request->validated('alasan'));

        return to_route('admin.ews.validasi.index')->with('sukses', "Status Awas {$wilayah->nama} dicabut. Status kembali mengikuti data sensor.");
    }
}
