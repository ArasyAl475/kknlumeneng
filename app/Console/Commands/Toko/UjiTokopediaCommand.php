<?php

namespace App\Console\Commands\Toko;

use App\Services\Toko\Tokopedia\GalatTokopedia;
use App\Services\Toko\Tokopedia\KlienTokopedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UjiTokopediaCommand extends Command
{
    protected $signature = 'toko:uji-tokopedia {--jumlah=3 : Banyak produk yang ditampilkan}';

    protected $description = 'Uji coba (hanya membaca): tampilkan beberapa produk dari Tokopedia. Tidak menulis ke database.';

    public function handle(KlienTokopedia $klien): int
    {
        $jumlah = max(1, min(10, (int) $this->option('jumlah')));

        try {
            $toko = $klien->daftarToko();

            if ($toko === []) {
                $this->warn('Tidak ada toko yang diizinkan untuk token ini.');

                return self::FAILURE;
            }

            $this->info('Toko terhubung: '.collect($toko)->map(fn ($t) => ($t['name'] ?? '?').' ('.($t['region'] ?? '?').')')->implode(', '));

            $cipher = (string) ($toko[0]['cipher'] ?? '');
            $daftar = $klien->cariProduk($cipher, $jumlah);
            $produk = array_slice($daftar['products'] ?? [], 0, $jumlah);

            $this->info('Jumlah produk aktif: '.($daftar['total_count'] ?? count($produk)));

            foreach ($produk as $baris) {
                $rincian = $klien->rincianProduk($cipher, (string) ($baris['id'] ?? ''));
                $harga = self::harga($rincian);

                $this->newLine();
                $this->line('Nama     : '.($rincian['title'] ?? $baris['title'] ?? '(kosong)'));
                $this->line('Harga    : '.($harga ?? '(tidak ditemukan; isian pada rincian: '.implode(', ', array_keys($rincian)).')'));
                $this->line('Deskripsi: '.Str::limit(trim(strip_tags((string) ($rincian['description'] ?? ''))), 80));
            }
        } catch (GalatTokopedia $galat) {
            // Pesan GalatTokopedia tidak memuat kunci atau token, jadi aman dicatat.
            Log::warning('Uji Tokopedia gagal: '.$galat->getMessage());
            $this->error('Gagal: '.$galat->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Harga ada di tingkat varian (SKU); varian pertama dipakai untuk uji coba ini.
     *
     * @param  array<string, mixed>  $rincian
     */
    public static function harga(array $rincian): ?string
    {
        $harga = data_get($rincian, 'skus.0.price.sale_price') ?? data_get($rincian, 'skus.0.price.tax_exclusive_price');

        if (! is_numeric($harga)) {
            return null;
        }

        return trim(data_get($rincian, 'skus.0.price.currency', 'IDR').' '.$harga);
    }
}
