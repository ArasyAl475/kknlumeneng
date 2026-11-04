<?php

namespace App\Services\Ews;

use App\Models\Ews\Aktivitas;
use App\Models\Ews\Pembacaan;
use App\Models\Ews\Wilayah;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class KeputusanAdmin
{
    public function __construct(private PemberitahuTelegram $telegram) {}

    /**
     * @return bool false bila laporan sudah diputuskan admin lain
     */
    public function setujui(Pembacaan $laporan, User $admin): bool
    {
        $mulaiAwas = false;

        $berhasil = DB::transaction(function () use ($laporan, $admin, &$mulaiAwas) {
            if (! $this->tandai($laporan, $admin, Pembacaan::VALIDASI_DISETUJUI, ['status' => PenilaianStatus::AWAS, 'warna' => PenilaianStatus::WARNA[PenilaianStatus::AWAS]])) {
                return false;
            }

            $wilayah = Wilayah::query()->lockForUpdate()->findOrFail($laporan->wilayah_id);
            $mulaiAwas = ! $wilayah->status_awas;

            if ($mulaiAwas) {
                $wilayah->update(['status_awas' => true, 'awas_sumber' => 'Dinyatakan admin setelah validasi', 'awas_sejak' => now()]);
            }

            Aktivitas::catat($admin, 'setujui_validasi', "Menyetujui laporan {$wilayah->nama} ({$laporan->pemicu}). Wilayah menjadi Awas.");

            return true;
        });

        DataDasbor::lupakan();

        // Telegram hanya sekali, saat wilayah benar-benar baru berubah menjadi Awas.
        if ($mulaiAwas) {
            $this->telegram->awasDisetujui($laporan->refresh()->load('wilayah'));
        }

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
