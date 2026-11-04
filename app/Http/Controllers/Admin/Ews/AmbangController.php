<?php

namespace App\Http\Controllers\Admin\Ews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ews\AmbangRequest;
use App\Models\Ews\Aktivitas;
use App\Models\Ews\Pengaturan;
use App\Services\Ews\DataDasbor;
use App\Services\Ews\PenilaianStatus;
use App\Services\Ews\Sensor;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AmbangController extends Controller
{
    public function edit(): View
    {
        return view('admin.ews.ambang', ['ambang' => Pengaturan::ambang(), 'sensor' => Sensor::DAFTAR]);
    }

    public function update(AmbangRequest $request): RedirectResponse
    {
        $lama = Pengaturan::ambang();
        $baru = $request->ambang();
        Pengaturan::simpanAmbang($baru);

        $perubahan = [];
        foreach ($baru as $sensor => $batas) {
            if ($batas !== $lama[$sensor]) {
                $perubahan[] = Sensor::DAFTAR[$sensor]['nama'].': '
                    .PenilaianStatus::angka($lama[$sensor]['waspada']).'/'.PenilaianStatus::angka($lama[$sensor]['validasi']).' menjadi '
                    .PenilaianStatus::angka($batas['waspada']).'/'.PenilaianStatus::angka($batas['validasi']);
            }
        }

        if ($perubahan) {
            Aktivitas::catat($request->user(), 'ubah_ambang', 'Mengubah ambang (waspada/validasi). '.implode('; ', $perubahan));
        }

        DataDasbor::lupakan();

        return back()->with('sukses', 'Ambang batas disimpan dan langsung berlaku untuk data berikutnya.');
    }
}
