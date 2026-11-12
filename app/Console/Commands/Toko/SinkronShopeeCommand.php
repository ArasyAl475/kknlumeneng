<?php

namespace App\Console\Commands\Toko;

use App\Services\Toko\Shopee\GalatShopee;
use App\Services\Toko\Shopee\SinkronProduk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SinkronShopeeCommand extends Command
{
    protected $signature = 'toko:sinkron-shopee';

    protected $description = 'Menarik nama, harga, dan deskripsi produk dari Shopee (data Shopee menimpa data di sini)';

    public function handle(SinkronProduk $sinkron): int
    {
        try {
            $hasil = $sinkron->jalankan();
        } catch (GalatShopee $galat) {
            // Pesan GalatShopee tidak memuat token, jadi aman dicatat.
            Log::warning('Sinkron produk Shopee gagal: '.$galat->getMessage());
            $this->error('Gagal: '.$galat->getMessage());

            return self::FAILURE;
        }

        $this->info("Selesai. Dibuat {$hasil['dibuat']}, diperbarui {$hasil['diperbarui']}, dilewati {$hasil['dilewati']}, gagal {$hasil['gagal']}.");

        return self::SUCCESS;
    }
}
