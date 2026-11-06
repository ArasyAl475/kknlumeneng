<?php

namespace App\Services\Toko;

use App\Models\Toko\Marketplace;
use App\Models\Toko\Produk;

/**
 * Satu-satunya aturan tombol beli, dipakai halaman publik dan panel admin:
 * tautan produk dulu, lalu tautan toko sebagai cadangan. Tanpa keduanya, produk
 * tidak ditawarkan di marketplace itu agar pengunjung tidak bertemu tombol mati.
 */
final class TautanBeli
{
    public const JENIS_PRODUK = 'produk';

    public const JENIS_TOKO = 'toko';

    /**
     * @return array{url: string, jenis: string}|null
     */
    public static function untuk(Produk $produk, Marketplace $marketplace): ?array
    {
        if (! $marketplace->aktif) {
            return null;
        }

        $tautanProduk = $produk->tautanUntuk($marketplace->kunci);

        if (filled($tautanProduk)) {
            return ['url' => $tautanProduk, 'jenis' => self::JENIS_PRODUK];
        }

        if (filled($marketplace->url_toko)) {
            return ['url' => $marketplace->url_toko, 'jenis' => self::JENIS_TOKO];
        }

        return null;
    }
}
