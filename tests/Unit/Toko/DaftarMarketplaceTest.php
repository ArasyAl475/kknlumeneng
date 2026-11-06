<?php

namespace Tests\Unit\Toko;

use App\Services\Toko\DaftarMarketplace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DaftarMarketplaceTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function tautanSah(): array
    {
        return [
            'shopee produk' => ['shopee', 'https://shopee.co.id/Kopi-Bubuk-i.123.456'],
            'shopee subdomain' => ['shopee', 'https://seller.shopee.co.id/portal/product'],
            'shopee tautan pendek' => ['shopee', 'https://shope.ee/AbCdEf'],
            'tokopedia www' => ['tokopedia', 'https://www.tokopedia.com/tokodesa/kopi'],
            'tokopedia tautan pendek' => ['tokopedia', 'https://tokopedia.link/AbC'],
            'facebook marketplace' => ['facebook', 'https://www.facebook.com/marketplace/item/123/'],
            'facebook mobile' => ['facebook', 'https://m.facebook.com/marketplace/item/123/'],
            'huruf besar di host' => ['shopee', 'https://SHOPEE.co.id/abc'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function tautanTidakSah(): array
    {
        return [
            'bukan https' => ['shopee', 'http://shopee.co.id/abc'],
            'situs lain' => ['shopee', 'https://contoh.com/shopee.co.id'],
            'domain tiruan di depan' => ['shopee', 'https://evil-shopee.co.id/abc'],
            'domain asli jadi subdomain penipu' => ['shopee', 'https://shopee.co.id.evil.com/abc'],
            'menyelipkan nama pengguna' => ['shopee', 'https://shopee.co.id@evil.com/abc'],
            'marketplace tertukar' => ['tokopedia', 'https://shopee.co.id/abc'],
            'bukan tautan' => ['facebook', 'facebook marketplace saya'],
            'skema javascript' => ['facebook', 'javascript:alert(1)'],
            'marketplace tidak dikenal' => ['lazada', 'https://www.lazada.co.id/abc'],
            'kosong' => ['shopee', ''],
        ];
    }

    #[DataProvider('tautanSah')]
    public function test_tautan_marketplace_yang_benar_diterima(string $kunci, string $url): void
    {
        $this->assertTrue(DaftarMarketplace::tautanSah($kunci, $url));
    }

    #[DataProvider('tautanTidakSah')]
    public function test_tautan_yang_salah_atau_menipu_ditolak(string $kunci, string $url): void
    {
        $this->assertFalse(DaftarMarketplace::tautanSah($kunci, $url));
    }

    public function test_tiap_marketplace_punya_nama_dan_domain(): void
    {
        $this->assertSame(['shopee', 'tokopedia', 'facebook'], DaftarMarketplace::KUNCI);

        foreach (DaftarMarketplace::KUNCI as $kunci) {
            $this->assertNotSame('', DaftarMarketplace::nama($kunci));
            $this->assertNotSame('', DaftarMarketplace::domainUtama($kunci));
        }
    }
}
