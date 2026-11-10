<?php

namespace Tests\Unit\Toko;

use App\Services\Toko\Shopee\GalatShopee;
use App\Services\Toko\Shopee\KlienShopee;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KlienShopeeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['toko.shopee' => ['partner_id' => '123', 'partner_key' => 'kunci-uji', 'sandbox' => true, 'host' => null]]);
        Http::preventStrayRequests();
        $this->travelTo(now()->setTimestamp(1700000000));
    }

    public function test_tanda_tangan_api_umum_mengikuti_urutan_shopee(): void
    {
        $this->assertSame(
            hash_hmac('sha256', '123/api/v2/auth/token/get1700000000', 'kunci-uji'),
            (new KlienShopee)->tandaTangan('/api/v2/auth/token/get', 1700000000),
        );
    }

    public function test_tanda_tangan_api_toko_menyertakan_token_dan_nomor_toko(): void
    {
        $this->assertSame(
            hash_hmac('sha256', '123/api/v2/shop/get_shop_info1700000000tokenA777', 'kunci-uji'),
            (new KlienShopee)->tandaTangan('/api/v2/shop/get_shop_info', 1700000000, 'tokenA', 777),
        );
    }

    public function test_url_izin_memakai_host_sandbox_dan_alamat_kembali(): void
    {
        $url = (new KlienShopee)->urlIzin('https://desa.test/kembali/abc');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith(KlienShopee::HOST_SANDBOX.KlienShopee::JALUR_IZIN.'?', $url);
        $this->assertSame('123', $query['partner_id']);
        $this->assertSame('1700000000', $query['timestamp']);
        $this->assertSame('https://desa.test/kembali/abc', $query['redirect']);
        $this->assertSame(hash_hmac('sha256', '123/api/v2/shop/auth_partner1700000000', 'kunci-uji'), $query['sign']);
    }

    public function test_mode_live_memakai_host_live(): void
    {
        config(['toko.shopee.sandbox' => false]);

        $this->assertStringStartsWith(KlienShopee::HOST_LIVE, (new KlienShopee)->urlIzin('https://desa.test/k'));
    }

    public function test_tanpa_kunci_tidak_siap_dan_tidak_mengirim_apa_pun(): void
    {
        config(['toko.shopee.partner_key' => '']);
        $klien = new KlienShopee;

        $this->assertFalse($klien->siap());
        $this->expectException(GalatShopee::class);
        $klien->tukarKode('kode', 777);
    }

    public function test_tukar_kode_mengirim_isian_yang_benar(): void
    {
        Http::fake(['*/api/v2/auth/token/get*' => Http::response(['access_token' => 'A1', 'refresh_token' => 'R1', 'expire_in' => 14400, 'error' => ''])]);

        $token = (new KlienShopee)->tukarKode('kode123', 777);

        $this->assertSame(['access_token' => 'A1', 'refresh_token' => 'R1', 'expire_in' => 14400], $token);
        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && $r['code'] === 'kode123' && $r['shop_id'] === 777 && $r['partner_id'] === 123
            && str_contains($r->url(), 'sign='.hash_hmac('sha256', '123/api/v2/auth/token/get1700000000', 'kunci-uji')));
    }

    public function test_galat_dari_shopee_menjadi_galat_shopee_tanpa_membocorkan_kunci(): void
    {
        Http::fake(['*' => Http::response(['error' => 'error_auth', 'message' => 'Invalid code'], 403)]);

        try {
            (new KlienShopee)->tukarKode('kode123', 777);
            $this->fail('Seharusnya galat.');
        } catch (GalatShopee $galat) {
            $this->assertSame('Invalid code (error_auth)', $galat->getMessage());
            $this->assertStringNotContainsString('kunci-uji', $galat->getMessage());
        }
    }

    public function test_balasan_tanpa_token_ditolak(): void
    {
        Http::fake(['*' => Http::response(['error' => '', 'access_token' => ''])]);

        $this->expectExceptionMessage('tidak berisi token');
        (new KlienShopee)->perpanjangToken('R1', 777);
    }
}
