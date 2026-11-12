<?php

namespace App\Services\Toko\Tokopedia;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Satu-satunya tempat yang memanggil TikTok Shop Partner API (jalur resmi toko Tokopedia).
 *
 * PERHATIAN: alamat, nomor versi jalur, aturan tanda tangan, dan nama isian di bawah ditulis dari
 * pengetahuan umum, BELUM dicocokkan dengan dokumentasi Partner Center. Bila uji coba gagal,
 * periksa bagian ini lebih dulu. Setiap permintaan ditandatangani HMAC-SHA256 dengan app secret.
 */
class KlienTokopedia
{
    public const HOST = 'https://open-api.tiktokglobalshop.com';

    public const JALUR_TOKO = '/authorization/202309/shops';

    public const JALUR_CARI = '/product/202312/products/search';

    public const JALUR_RINCIAN = '/product/202309/products/';

    private string $appKey;

    private string $appSecret;

    private string $aksesToken;

    private string $host;

    public function __construct()
    {
        $c = config('toko.tokopedia');
        $this->appKey = (string) ($c['app_key'] ?? '');
        $this->appSecret = (string) ($c['app_secret'] ?? '');
        $this->aksesToken = (string) ($c['access_token'] ?? '');
        $host = (string) ($c['host'] ?? '');
        $this->host = rtrim($host !== '' ? $host : self::HOST, '/');
    }

    public function siap(): bool
    {
        return $this->appKey !== '' && $this->appSecret !== '';
    }

    public function punyaToken(): bool
    {
        return $this->aksesToken !== '';
    }

    /**
     * Teks dasar: app secret + jalur + pasangan nama-nilai parameter URL yang diurutkan abjad
     * (tanpa "sign" dan "access_token") + isi JSON + app secret.
     *
     * @param  array<string, scalar>  $query
     */
    public function tandaTangan(string $jalur, array $query, string $badan = ''): string
    {
        unset($query['sign'], $query['access_token']);
        ksort($query);

        $gabungan = '';
        foreach ($query as $nama => $nilai) {
            $gabungan .= $nama.$nilai;
        }

        return hash_hmac('sha256', $this->appSecret.$jalur.$gabungan.$badan.$this->appSecret, $this->appSecret);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function daftarToko(): array
    {
        return $this->panggil('GET', self::JALUR_TOKO)['shops'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function cariProduk(string $cipherToko, int $ukuran = 10, ?string $halaman = null): array
    {
        $query = ['shop_cipher' => $cipherToko, 'page_size' => $ukuran];

        if ($halaman !== null && $halaman !== '') {
            $query['page_token'] = $halaman;
        }

        // Hanya produk yang sedang dijual.
        return $this->panggil('POST', self::JALUR_CARI, $query, ['status' => 'ACTIVATE']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rincianProduk(string $cipherToko, string $idProduk): array
    {
        return $this->panggil('GET', self::JALUR_RINCIAN.rawurlencode($idProduk), ['shop_cipher' => $cipherToko]);
    }

    /**
     * @param  array<string, scalar>  $query
     * @param  array<string, mixed>|null  $badan
     * @return array<string, mixed> isi "data" dari balasan
     */
    private function panggil(string $metode, string $jalur, array $query = [], ?array $badan = null): array
    {
        if (! $this->siap()) {
            throw GalatTokopedia::belumDiatur();
        }

        if (! $this->punyaToken()) {
            throw GalatTokopedia::tokenBelumDiisi();
        }

        $query += ['app_key' => $this->appKey, 'timestamp' => now()->timestamp];

        // Isi yang ditandatangani harus sama persis dengan yang dikirim, jadi dibuat satu kali.
        $json = $badan === null ? '' : (string) json_encode($badan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $query['sign'] = $this->tandaTangan($jalur, $query, $json);

        $url = $this->host.$jalur.'?'.http_build_query($query);

        try {
            $permintaan = Http::acceptJson()
                ->withHeaders(['x-tts-access-token' => $this->aksesToken])
                ->connectTimeout(5)
                ->timeout(15);

            $respons = $metode === 'GET'
                ? $permintaan->get($url)
                : $permintaan->withBody($json, 'application/json')->post($url);
        } catch (ConnectionException) {
            // Pesan asli memuat alamat lengkap, jadi sengaja tidak diteruskan.
            throw new GalatTokopedia('Tidak bisa menghubungi TikTok Shop. Periksa koneksi internet lalu coba lagi.');
        }

        $isi = $respons->json();

        if (! is_array($isi)) {
            throw new GalatTokopedia("Balasan TikTok Shop tidak bisa dibaca (HTTP {$respons->status()}).");
        }

        // Keberhasilan ditandai code 0; galat bisa datang dengan HTTP 200.
        if ((int) ($isi['code'] ?? -1) !== 0) {
            throw GalatTokopedia::dariBalasan($isi);
        }

        if ($respons->failed()) {
            throw new GalatTokopedia("TikTok Shop menolak permintaan (HTTP {$respons->status()}).");
        }

        return is_array($isi['data'] ?? null) ? $isi['data'] : [];
    }
}
