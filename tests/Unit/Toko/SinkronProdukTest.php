<?php

namespace Tests\Unit\Toko;

use App\Services\Toko\Shopee\SinkronProduk;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SinkronProdukTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: ?string}>
     */
    public static function alamat(): array
    {
        return [
            'alamat panjang bernama' => ['https://shopee.co.id/Kopi-Robusta-i.777.1001', '1001'],
            'alamat product' => ['https://shopee.co.id/product/777/1001', '1001'],
            'alamat pendek tidak bisa dibaca' => ['https://shope.ee/AbCdEf', null],
            'bukan produk' => ['https://shopee.co.id/kopi_lumeneng', null],
        ];
    }

    #[DataProvider('alamat')]
    public function test_id_dibaca_dari_alamat(string $url, ?string $diharapkan): void
    {
        $this->assertSame($diharapkan, SinkronProduk::idDariUrl($url));
    }

    public function test_harga_dibulatkan_ke_rupiah_utuh_dan_kosong_bila_tak_ada(): void
    {
        $this->assertSame(35001, SinkronProduk::harga(['price_info' => [['current_price' => 35000.6]]]));
        $this->assertSame(0, SinkronProduk::harga(['price_info' => [['current_price' => 0]]]));
        $this->assertNull(SinkronProduk::harga(['price_info' => []]));
        $this->assertNull(SinkronProduk::harga([]));
        $this->assertNull(SinkronProduk::harga(['price_info' => [['current_price' => -5]]]));
    }

    public function test_deskripsi_kosong_menjadi_null_dan_terlalu_panjang_dipotong(): void
    {
        $this->assertNull(SinkronProduk::deskripsi(['description' => "  \n "]));
        $this->assertSame(10000, mb_strlen((string) SinkronProduk::deskripsi(['description' => str_repeat('a', 12000)])));
    }
}
