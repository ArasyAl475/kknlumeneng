<?php

namespace App\Console\Commands\Ews;

use App\Models\Ews\Pembacaan;
use Illuminate\Console\Command;

class BersihkanCommand extends Command
{
    protected $signature = 'ews:bersihkan {--hari=90 : hapus pembacaan yang lebih tua dari sekian hari}';

    protected $description = 'Menghapus pembacaan sensor lama, kecuali yang pernah dinilai admin';

    public function handle(): int
    {
        $hari = (int) $this->option('hari');

        if ($hari < 7) {
            $this->error('Batas minimal 7 hari, supaya grafik 7 hari di dashboard tetap utuh.');

            return self::INVALID;
        }

        $batas = now()->subDays($hari);
        $terhapus = 0;

        // Dihapus bertahap karena tabel bisa besar dan hosting bersama sering membatasi lama query.
        // Laporan yang pernah divalidasi disimpan sebagai jejak keputusan.
        do {
            $ids = Pembacaan::query()
                ->where('created_at', '<', $batas)
                ->whereNull('validasi')
                ->orderBy('id')
                ->limit(1000)
                ->pluck('id');

            $terhapus += Pembacaan::query()->whereIn('id', $ids)->delete();
        } while ($ids->isNotEmpty());

        $this->info("{$terhapus} pembacaan lama dihapus.");

        return self::SUCCESS;
    }
}
