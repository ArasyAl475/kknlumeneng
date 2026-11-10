<?php

namespace App\Console\Commands\Toko;

use App\Services\Toko\Shopee\GalatShopee;
use App\Services\Toko\Shopee\KoneksiShopee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PerpanjangTokenShopeeCommand extends Command
{
    protected $signature = 'toko:perpanjang-token-shopee {--paksa : perpanjang walau token masih lama berlaku}';

    protected $description = 'Memperpanjang izin akses Shopee sebelum kedaluwarsa (dijalankan penjadwal tiap jam)';

    public function handle(KoneksiShopee $koneksi): int
    {
        try {
            $hasil = $koneksi->perpanjangBilaPerlu((bool) $this->option('paksa'));
        } catch (GalatShopee $galat) {
            // Pesan GalatShopee tidak memuat token, jadi aman dicatat.
            Log::warning('Perpanjang token Shopee gagal: '.$galat->getMessage());
            $this->error('Gagal: '.$galat->getMessage().' Bila berulang, hubungkan ulang toko di panel admin.');

            return self::FAILURE;
        }

        $this->info(match ($hasil) {
            KoneksiShopee::HASIL_TIDAK_ADA => 'Belum ada toko Shopee yang terhubung.',
            KoneksiShopee::HASIL_BELUM_PERLU => 'Token Shopee masih berlaku, belum perlu diperpanjang.',
            default => 'Token Shopee berhasil diperpanjang.',
        });

        return self::SUCCESS;
    }
}
