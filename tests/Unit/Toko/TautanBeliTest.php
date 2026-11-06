<?php

namespace Tests\Unit\Toko;

use App\Models\Toko\Marketplace;
use App\Models\Toko\Produk;
use App\Models\Toko\TautanProduk;
use App\Services\Toko\TautanBeli;
use Tests\TestCase;

class TautanBeliTest extends TestCase
{
    private function produk(array $tautan = []): Produk
    {
        $produk = new Produk(['nama' => 'Kopi Bubuk']);
        $produk->setRelation('tautan', collect($tautan)->map(
            fn (string $url, string $kunci) => new TautanProduk(['marketplace' => $kunci, 'url' => $url])
        )->values());

        return $produk;
    }

    private function marketplace(string $kunci, ?string $urlToko = null, bool $aktif = true): Marketplace
    {
        return new Marketplace(['kunci' => $kunci, 'url_toko' => $urlToko, 'aktif' => $aktif]);
    }

    public function test_tautan_produk_diutamakan(): void
    {
        $hasil = TautanBeli::untuk(
            $this->produk(['shopee' => 'https://shopee.co.id/kopi']),
            $this->marketplace('shopee', 'https://shopee.co.id/toko'),
        );

        $this->assertSame(['url' => 'https://shopee.co.id/kopi', 'jenis' => TautanBeli::JENIS_PRODUK], $hasil);
    }

    public function test_tautan_toko_dipakai_bila_tautan_produk_kosong(): void
    {
        $hasil = TautanBeli::untuk(
            $this->produk(['tokopedia' => 'https://www.tokopedia.com/kopi']),
            $this->marketplace('shopee', 'https://shopee.co.id/toko'),
        );

        $this->assertSame(['url' => 'https://shopee.co.id/toko', 'jenis' => TautanBeli::JENIS_TOKO], $hasil);
    }

    public function test_tanpa_tautan_apa_pun_tidak_ada_tombol(): void
    {
        $this->assertNull(TautanBeli::untuk($this->produk(), $this->marketplace('facebook')));
    }

    public function test_marketplace_nonaktif_tidak_ada_tombol(): void
    {
        $hasil = TautanBeli::untuk(
            $this->produk(['shopee' => 'https://shopee.co.id/kopi']),
            $this->marketplace('shopee', 'https://shopee.co.id/toko', aktif: false),
        );

        $this->assertNull($hasil);
    }
}
