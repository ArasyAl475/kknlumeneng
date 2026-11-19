<?php

namespace App\Console\Commands\Ews;

use App\Services\Ews\NotifikasiWarga;
use Illuminate\Console\Command;

class PantauNotifikasiCommand extends Command
{
    protected $signature = 'ews:pantau-notifikasi';

    protected $description = 'Memeriksa status wilayah dan mengirim notifikasi HP bila berubah (dijalankan penjadwal tiap menit)';

    // Kiriman alat dan keputusan admin sudah memicu pemeriksaan sendiri. Perintah ini menangkap yang tidak
    // punya pemicu: alat berhenti mengirim data, dan pengiriman ulang notifikasi yang sebelumnya gagal.
    public function handle(NotifikasiWarga $notifikasi): int
    {
        $this->info($notifikasi->periksa().' notifikasi terkirim.');

        return self::SUCCESS;
    }
}
