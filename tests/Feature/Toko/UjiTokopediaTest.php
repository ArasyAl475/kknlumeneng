<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UjiTokopediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['toko.tokopedia' => ['app_key' => 'kunci', 'app_secret' => 'rahasia-uji', 'access_token' => 'token-uji', 'host' => null]]);
        Http::preventStrayRequests();
    }

    public function test_perintah_menampilkan_produk_tanpa_menulis_ke_database(): void
    {
        Http::fake([
            '*/authorization/202309/shops*' => Http::response(['code' => 0, 'data' => ['shops' => [['name' => 'Toko Uji', 'region' => 'ID', 'cipher' => 'C1']]]]),
            '*/product/202312/products/search*' => Http::response(['code' => 0, 'data' => ['total_count' => 1, 'products' => [['id' => 'P1', 'title' => 'Kopi']]]]),
            '*/product/202309/products/P1*' => Http::response(['code' => 0, 'data' => [
                'title' => 'Kopi Lumeneng',
                'description' => '<p>Kopi <b>enak</b></p>',
                'skus' => [['price' => ['sale_price' => '25000', 'currency' => 'IDR']]],
            ]]),
        ]);

        $this->artisan('toko:uji-tokopedia')
            ->expectsOutputToContain('Toko Uji (ID)')
            ->expectsOutputToContain('Kopi Lumeneng')
            ->expectsOutputToContain('IDR 25000')
            ->expectsOutputToContain('Kopi enak')
            ->assertSuccessful();

        $this->assertSame(0, Produk::count());
    }

    public function test_perintah_gagal_dengan_pesan_jelas_bila_token_ditolak(): void
    {
        Http::fake(['*' => Http::response(['code' => 36004004, 'message' => 'Invalid access token'], 401)]);

        $this->artisan('toko:uji-tokopedia')
            ->expectsOutputToContain('Invalid access token')
            ->assertFailed();
    }

    public function test_perintah_menyebut_isian_yang_ada_bila_harga_tidak_ditemukan(): void
    {
        Http::fake([
            '*/authorization/202309/shops*' => Http::response(['code' => 0, 'data' => ['shops' => [['name' => 'T', 'region' => 'ID', 'cipher' => 'C1']]]]),
            '*/product/202312/products/search*' => Http::response(['code' => 0, 'data' => ['products' => [['id' => 'P1']]]]),
            '*/product/202309/products/P1*' => Http::response(['code' => 0, 'data' => ['title' => 'Kopi', 'harga_aneh' => 1]]),
        ]);

        $this->artisan('toko:uji-tokopedia')
            ->expectsOutputToContain('tidak ditemukan; isian pada rincian: title, harga_aneh')
            ->assertSuccessful();
    }
}
