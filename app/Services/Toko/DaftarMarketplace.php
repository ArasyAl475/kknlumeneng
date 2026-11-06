<?php

namespace App\Services\Toko;

/**
 * Satu-satunya tempat yang tahu marketplace apa saja yang didukung, nama tampilannya,
 * dan domain mana yang sah untuk tautannya.
 */
final class DaftarMarketplace
{
    public const KUNCI = ['shopee', 'tokopedia', 'facebook'];

    /**
     * Domain pendek (shope.ee, tokopedia.link, fb.me) ikut diizinkan karena tombol "Bagikan"
     * di aplikasi marketplace sering menghasilkan tautan pendek seperti itu.
     */
    private const DATA = [
        'shopee' => [
            'nama' => 'Shopee',
            'domain' => ['shopee.co.id', 'shope.ee'],
            'huruf' => 'S',
            'warna' => 'bg-[#c2371c]',
        ],
        'tokopedia' => [
            'nama' => 'Tokopedia',
            'domain' => ['tokopedia.com', 'tokopedia.link'],
            'huruf' => 'T',
            'warna' => 'bg-[#0a7d12]',
        ],
        'facebook' => [
            'nama' => 'Facebook',
            'domain' => ['facebook.com', 'fb.com', 'fb.me'],
            'huruf' => 'f',
            'warna' => 'bg-[#1565d8]',
        ],
    ];

    public static function ada(string $kunci): bool
    {
        return isset(self::DATA[$kunci]);
    }

    public static function nama(string $kunci): string
    {
        return self::DATA[$kunci]['nama'];
    }

    public static function domainUtama(string $kunci): string
    {
        return self::DATA[$kunci]['domain'][0];
    }

    public static function huruf(string $kunci): string
    {
        return self::DATA[$kunci]['huruf'];
    }

    public static function warna(string $kunci): string
    {
        return self::DATA[$kunci]['warna'];
    }

    /**
     * Logo disediakan tim sebagai berkas di public/images/toko/. Selama belum ada,
     * tampilan memakai huruf pengganti, jadi tidak ada gambar rusak.
     */
    public static function logo(string $kunci): ?string
    {
        foreach (['svg', 'png', 'webp'] as $ekstensi) {
            if (is_file(public_path("images/toko/{$kunci}.{$ekstensi}"))) {
                return asset("images/toko/{$kunci}.{$ekstensi}");
            }
        }

        return null;
    }

    /**
     * Tautan sah bila https dan host-nya domain marketplace itu atau subdomainnya.
     * Mencocokkan akhiran dengan titik di depannya menolak tiruan seperti "evil-shopee.co.id"
     * dan "shopee.co.id.evil.com".
     */
    public static function tautanSah(string $kunci, string $url): bool
    {
        if (! self::ada($kunci) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $bagian = parse_url($url);

        if (strtolower($bagian['scheme'] ?? '') !== 'https' || isset($bagian['user']) || isset($bagian['pass'])) {
            return false;
        }

        $host = strtolower($bagian['host'] ?? '');

        foreach (self::DATA[$kunci]['domain'] as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }
}
