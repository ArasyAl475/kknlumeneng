<?php

namespace App\Services\Toko\Shopee;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Satu-satunya tempat yang memanggil Shopee Open Platform API v2.
 * Setiap permintaan ditandatangani HMAC-SHA256 dengan partner key; Shopee menolak tanda tangan
 * yang umurnya lebih dari 5 menit, jadi stempel waktu selalu dibuat tepat sebelum mengirim.
 */
class KlienShopee
{
    public const HOST_LIVE = 'https://partner.shopeemobile.com';

    public const HOST_SANDBOX = 'https://partner.test-stable.shopeemobile.com';

    public const JALUR_IZIN = '/api/v2/shop/auth_partner';

    public const JALUR_TOKEN = '/api/v2/auth/token/get';

    public const JALUR_PERPANJANG = '/api/v2/auth/access_token/get';

    public const JALUR_INFO_TOKO = '/api/v2/shop/get_shop_info';

    private int $partnerId;

    private string $partnerKey;

    private string $host;

    public function __construct()
    {
        $c = config('toko.shopee');
        $this->partnerId = (int) ($c['partner_id'] ?? 0);
        $this->partnerKey = (string) ($c['partner_key'] ?? '');
        $host = (string) ($c['host'] ?? '');
        $this->host = rtrim($host !== '' ? $host : (($c['sandbox'] ?? true) ? self::HOST_SANDBOX : self::HOST_LIVE), '/');
    }

    public function siap(): bool
    {
        return $this->partnerId > 0 && $this->partnerKey !== '';
    }

    /**
     * Urutan teks dasar ditetapkan Shopee: partner_id, jalur, waktu, lalu (khusus API toko) access_token dan shop_id.
     */
    public function tandaTangan(string $jalur, int $waktu, string $aksesToken = '', ?int $shopId = null): string
    {
        return hash_hmac('sha256', $this->partnerId.$jalur.$waktu.$aksesToken.($shopId ?? ''), $this->partnerKey);
    }

    /** Halaman Shopee tempat pemilik toko login dan menyetujui akses; setelah itu Shopee kembali ke $kembaliKe. */
    public function urlIzin(string $kembaliKe): string
    {
        if (! $this->siap()) {
            throw GalatShopee::belumDiatur();
        }

        $waktu = now()->timestamp;

        return $this->host.self::JALUR_IZIN.'?'.http_build_query([
            'partner_id' => $this->partnerId,
            'timestamp' => $waktu,
            'sign' => $this->tandaTangan(self::JALUR_IZIN, $waktu),
            'redirect' => $kembaliKe,
        ]);
    }

    /**
     * Kode dari halaman izin hanya bisa ditukar sekali dan cepat kedaluwarsa, jadi tidak dicoba ulang.
     *
     * @return array{access_token: string, refresh_token: string, expire_in: int}
     */
    public function tukarKode(string $kode, int $shopId): array
    {
        return $this->ambilToken($this->kirimPublik(self::JALUR_TOKEN, [
            'code' => $kode,
            'shop_id' => $shopId,
            'partner_id' => $this->partnerId,
        ]));
    }

    /**
     * Shopee mengganti refresh token setiap kali dipakai; yang lama tidak berlaku lagi.
     *
     * @return array{access_token: string, refresh_token: string, expire_in: int}
     */
    public function perpanjangToken(string $refreshToken, int $shopId): array
    {
        return $this->ambilToken($this->kirimPublik(self::JALUR_PERPANJANG, [
            'refresh_token' => $refreshToken,
            'shop_id' => $shopId,
            'partner_id' => $this->partnerId,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function infoToko(string $aksesToken, int $shopId): array
    {
        return $this->panggilToko('GET', self::JALUR_INFO_TOKO, $aksesToken, $shopId);
    }

    /**
     * Panggilan API atas nama satu toko (produk, pesanan, dst.).
     *
     * @param  array<string, mixed>  $data  parameter URL untuk GET, isi JSON untuk POST
     * @return array<string, mixed>
     */
    public function panggilToko(string $metode, string $jalur, string $aksesToken, int $shopId, array $data = []): array
    {
        $waktu = now()->timestamp;
        $query = [
            'partner_id' => $this->partnerId,
            'timestamp' => $waktu,
            'access_token' => $aksesToken,
            'shop_id' => $shopId,
            'sign' => $this->tandaTangan($jalur, $waktu, $aksesToken, $shopId),
        ];

        return $metode === 'GET'
            ? $this->kirim('GET', $jalur, $query + $data, null)
            : $this->kirim('POST', $jalur, $query, $data);
    }

    /**
     * @param  array<string, mixed>  $badan
     * @return array<string, mixed>
     */
    private function kirimPublik(string $jalur, array $badan): array
    {
        $waktu = now()->timestamp;

        return $this->kirim('POST', $jalur, [
            'partner_id' => $this->partnerId,
            'timestamp' => $waktu,
            'sign' => $this->tandaTangan($jalur, $waktu),
        ], $badan);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $badan
     * @return array<string, mixed>
     */
    private function kirim(string $metode, string $jalur, array $query, ?array $badan): array
    {
        if (! $this->siap()) {
            throw GalatShopee::belumDiatur();
        }

        $url = $this->host.$jalur.'?'.http_build_query($query);

        try {
            $permintaan = Http::acceptJson()->connectTimeout(5)->timeout(15);
            $respons = $metode === 'GET' ? $permintaan->get($url) : $permintaan->post($url, $badan ?? []);
        } catch (ConnectionException) {
            // Pesan asli memuat alamat lengkap berisi token, jadi sengaja tidak diteruskan.
            throw new GalatShopee('Tidak bisa menghubungi Shopee. Periksa koneksi internet lalu coba lagi.');
        }

        $isi = $respons->json();

        if (! is_array($isi)) {
            throw new GalatShopee("Balasan Shopee tidak bisa dibaca (HTTP {$respons->status()}).");
        }

        // Shopee menandai galat lewat isian "error" yang tidak kosong, kadang dengan HTTP 200.
        if (($isi['error'] ?? '') !== '') {
            throw GalatShopee::dariBalasan($isi);
        }

        if ($respons->failed()) {
            throw new GalatShopee("Shopee menolak permintaan (HTTP {$respons->status()}).");
        }

        return $isi;
    }

    /**
     * @param  array<string, mixed>  $isi
     * @return array{access_token: string, refresh_token: string, expire_in: int}
     */
    private function ambilToken(array $isi): array
    {
        $akses = $isi['access_token'] ?? null;
        $refresh = $isi['refresh_token'] ?? null;

        if (! is_string($akses) || $akses === '' || ! is_string($refresh) || $refresh === '') {
            throw new GalatShopee('Balasan Shopee tidak berisi token.');
        }

        return [
            'access_token' => $akses,
            'refresh_token' => $refresh,
            'expire_in' => max(60, (int) ($isi['expire_in'] ?? 14400)),
        ];
    }
}
