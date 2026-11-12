<?php

namespace App\Services\Toko\Tokopedia;

use RuntimeException;

/**
 * Kegagalan saat berbicara dengan TikTok Shop (tempat Tokopedia). Pesannya aman ditampilkan dan
 * dicatat: tidak pernah memuat app secret, token, atau alamat lengkap permintaan.
 */
class GalatTokopedia extends RuntimeException
{
    public static function belumDiatur(): self
    {
        return new self('Kunci Tokopedia belum diisi. Isi TOKOPEDIA_APP_KEY dan TOKOPEDIA_APP_SECRET di berkas .env.');
    }

    public static function tokenBelumDiisi(): self
    {
        return new self('Token akses Tokopedia belum diisi. Isi TOKOPEDIA_ACCESS_TOKEN di berkas .env (khusus uji coba).');
    }

    /**
     * @param  array<string, mixed>  $balasan
     */
    public static function dariBalasan(array $balasan): self
    {
        $kode = (string) ($balasan['code'] ?? 'tidak_diketahui');
        $pesan = trim((string) ($balasan['message'] ?? ''));

        return new self($pesan !== '' ? "{$pesan} ({$kode})" : $kode);
    }
}
