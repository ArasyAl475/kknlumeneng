<?php

namespace App\Console\Commands\Ews;

use App\Models\Ews\Wilayah;
use App\Services\Ews\PemberitahuTelegram;
use App\Services\Ews\PenerimaPembacaan;
use App\Services\Ews\PenilaianStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CekAlatCommand extends Command
{
    protected $signature = 'ews:cek-alat';

    protected $description = 'Mengirim Telegram sekali saat alat suatu wilayah berhenti mengirim data (butuh cron)';

    public function handle(PemberitahuTelegram $telegram, PenerimaPembacaan $penerima): int
    {
        $batas = (int) config('ews.alat_offline_menit');

        foreach (Wilayah::query()->with('pembacaanTerbaru')->get() as $wilayah) {
            $terakhir = $wilayah->pembacaanTerbaru?->created_at;

            // Penanda dihapus PenerimaPembacaan begitu data masuk lagi, sehingga pesan hanya terkirim
            // sekali per kejadian mati. Cache::add juga mencegah dua cron berjalan bersamaan.
            if (PenilaianStatus::alatOffline($terakhir, $batas) && Cache::add($penerima->kunciOffline($wilayah), true, now()->addDays(30))) {
                $telegram->alatOffline($wilayah, $terakhir);
                $this->line("Alat {$wilayah->nama} tidak mengirim data.");
            }
        }

        return self::SUCCESS;
    }
}
