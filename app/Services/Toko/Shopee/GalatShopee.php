<?php

namespace App\Services\Toko\Shopee;

use RuntimeException;

/**
 * Kegagalan saat berbicara dengan Shopee. Pesannya aman ditampilkan ke admin dan dicatat:
 * tidak pernah memuat partner key, token, atau alamat lengkap permintaan.
 */
class GalatShopee extends RuntimeException
{
    public static function belumDiatur(): self
    {
        return new self('Kunci Shopee belum diisi. Isi SHOPEE_PARTNER_ID dan SHOPEE_PARTNER_KEY di berkas .env.');
    }

    /**
     * @param  array<string, mixed>  $balasan
     */
    public static function dariBalasan(array $balasan): self
    {
        $kode = (string) ($balasan['error'] ?? 'tidak_diketahui');
        $pesan = trim((string) ($balasan['message'] ?? ''));

        return new self($pesan !== '' ? "{$pesan} ({$kode})" : $kode);
    }
}
