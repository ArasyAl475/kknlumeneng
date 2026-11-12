<?php

namespace App\Services\Ews;

use App\Models\Ews\Aktivitas;
use App\Models\Ews\Pembacaan;
use App\Models\Ews\Wilayah;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class KeputusanAdmin
{
    /**
     * @return bool false bila laporan sudah diputuskan admin lain
     */
    public function setujui(Pembacaan $laporan, User $admin): bool
    {
        $berhasil = DB::transaction(function () use ($laporan, $admin) {
            if (! $this->tandai($laporan, $admin, Pembacaan::VALIDASI_DISETUJUI, ['status' => PenilaianStatus::AWAS, 'warna' => PenilaianStatus::WARNA[PenilaianStatus::AWAS]])) {
                return false;
            }

            $wilayah = Wilayah::query()->lockForUpdate()->findOrFail($laporan->wilayah_id);

            if (! $wilayah->status_awas) {
                $wilayah->update(['status_awas' => true, 'awas_sumber' => 'Dinyatakan admin setelah validasi', 'awas_sejak' => now()]);
            }

            Aktivitas::catat($admin, 'setujui_validasi', "Menyetujui laporan {$wilayah->nama} ({$laporan->pemicu}). Wilayah menjadi Awas.");

            return true;
        });

        DataDasbor::lupakan();

        return $berhasil;
    }

    /**
     * @return bool false bila laporan sudah diputuskan admin lain
     */
    public function tolak(Pembacaan $laporan, User $admin): bool
    {
        $berhasil = DB::transaction(function () use ($laporan, $admin) {
            if (! $this->tandai($laporan, $admin, Pembacaan::VALIDASI_DITOLAK)) {
                return false;
            }

            Aktivitas::catat($admin, 'tolak_validasi', "Menolak laporan {$laporan->wilayah->nama} ({$laporan->pemicu}).");

            return true;
        });

        DataDasbor::lupakan();

        return $berhasil;
    }

    /**
     * Admin menyatakan Awas sendiri, tanpa laporan sensor: mis. alat berhenti saat Waspada, atau kader
     * melihat retakan di lapangan. Alasan hanya masuk log admin; yang tampil ke warga cukup sumbernya.
     *
     * @return bool false bila wilayah sudah berstatus Awas
     */
    public function nyatakanAwas(Wilayah $wilayah, User $admin, string $alasan): bool
    {
        $berhasil = DB::transaction(function () use ($wilayah, $admin, $alasan) {
            $terkunci = Wilayah::query()->lockForUpdate()->findOrFail($wilayah->id);

            if ($terkunci->status_awas) {
                return false;
            }

            $terkunci->update(['status_awas' => true, 'awas_sumber' => 'Dinyatakan admin (manual)', 'awas_sejak' => now()]);
            Aktivitas::catat($admin, 'nyatakan_awas', "Menyatakan Awas {$terkunci->nama} secara manual. Alasan: {$alasan}");

            return true;
        });

        DataDasbor::lupakan();

        return $berhasil;
    }

    public function cabutAwas(Wilayah $wilayah, User $admin, string $alasan): void
    {
        DB::transaction(function () use ($wilayah, $admin, $alasan) {
            $wilayah->update(['status_awas' => false, 'awas_sumber' => null, 'awas_sejak' => null]);
            Aktivitas::catat($admin, 'cabut_awas', "Mencabut status Awas {$wilayah->nama}. Alasan: {$alasan}");
        });

        DataDasbor::lupakan();
    }

    // Pembaruan bersyarat: bila dua admin menekan bersamaan, hanya satu yang berhasil.
    private function tandai(Pembacaan $laporan, User $admin, string $hasil, array $tambahan = []): bool
    {
        return Pembacaan::query()
            ->whereKey($laporan->id)
            ->where('validasi', Pembacaan::VALIDASI_MENUNGGU)
            ->update(['validasi' => $hasil, 'divalidasi_pada' => now(), 'divalidasi_oleh' => $admin->id, ...$tambahan]) === 1;
    }
}
