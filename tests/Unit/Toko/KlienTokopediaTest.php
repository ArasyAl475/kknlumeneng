<?php

namespace Tests\Unit\Toko;

use App\Services\Toko\Tokopedia\GalatTokopedia;
use App\Services\Toko\Tokopedia\KlienTokopedia;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KlienTokopediaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['toko.tokopedia' => ['app_key' => 'kunci', 'app_secret' => 'rahasia-uji', 'access_token' => 'token-uji', 'host' => null]]);
        Http::preventStrayRequests();
        $this->travelTo(now()->setTimestamp(1700000000));
    }

    public function test_tanda_tangan_mengurutkan_parameter_dan_menyertakan_isi(): void
    {
        $jalur = '/product/202312/products/search';
        $isi = '{"status":"ACTIVATE"}';
        $query = ['timestamp' => 1700000000, 'shop_cipher' => 'c', 'app_key' => 'k', 'page_size' => 5];

        $this->assertSame(
            hash_hmac('sha256', 'rahasia-uji'.$jalur.'app_keykpage_size5shop_cipherctimestamp1700000000'.$isi.'rahasia-uji', 'rahasia-uji'),
            (new KlienTokopedia)->tandaTangan($jalur, $query, $isi),
        );
    }

    public function test_tanda_tangan_mengabaikan_sign_dan_access_token(): void
    {
        $klien = new KlienTokopedia;

        $this->assertSame(
            $klien->tandaTangan('/x', ['app_key' => 'k']),
            $klien->tandaTangan('/x', ['app_key' => 'k', 'sign' => 'lama', 'access_token' => 'rahasia']),
        );
    }

    public function test_permintaan_membawa_header_token_dan_tanda_tangan(): void
    {
        Http::fake(['*' => Http::response(['code' => 0, 'data' => ['shops' => [['name' => 'Toko Uji']]]])]);

        $toko = (new KlienTokopedia)->daftarToko();

        $this->assertSame('Toko Uji', $toko[0]['name']);
        Http::assertSent(function (Request $r) {
            parse_str((string) parse_url($r->url(), PHP_URL_QUERY), $query);

            return $r->method() === 'GET'
                && $r->hasHeader('x-tts-access-token', 'token-uji')
                && $query['app_key'] === 'kunci'
                && $query['timestamp'] === '1700000000'
                && ! array_key_exists('access_token', $query)
                && $query['sign'] === (new KlienTokopedia)->tandaTangan(KlienTokopedia::JALUR_TOKO, ['app_key' => 'kunci', 'timestamp' => 1700000000]);
        });
    }

    public function test_cari_produk_mengirim_isi_json_yang_ikut_ditandatangani(): void
    {
        Http::fake(['*' => Http::response(['code' => 0, 'data' => ['products' => []]])]);

        (new KlienTokopedia)->cariProduk('cipherA', 5);

        Http::assertSent(function (Request $r) {
            parse_str((string) parse_url($r->url(), PHP_URL_QUERY), $query);
            $diharapkan = (new KlienTokopedia)->tandaTangan(
                KlienTokopedia::JALUR_CARI,
                ['shop_cipher' => 'cipherA', 'page_size' => 5, 'app_key' => 'kunci', 'timestamp' => 1700000000],
                '{"status":"ACTIVATE"}',
            );

            return $r->method() === 'POST' && $r->body() === '{"status":"ACTIVATE"}' && $query['sign'] === $diharapkan;
        });
    }

    public function test_tanpa_kunci_atau_token_tidak_mengirim_apa_pun(): void
    {
        config(['toko.tokopedia.app_secret' => '']);
        $this->assertFalse((new KlienTokopedia)->siap());

        try {
            (new KlienTokopedia)->daftarToko();
            $this->fail('Seharusnya galat.');
        } catch (GalatTokopedia $galat) {
            $this->assertStringContainsString('TOKOPEDIA_APP_KEY', $galat->getMessage());
        }

        config(['toko.tokopedia.app_secret' => 'rahasia-uji', 'toko.tokopedia.access_token' => '']);
        $this->expectExceptionMessage('TOKOPEDIA_ACCESS_TOKEN');
        (new KlienTokopedia)->daftarToko();
    }

    public function test_galat_dari_tiktok_shop_tidak_membocorkan_rahasia(): void
    {
        Http::fake(['*' => Http::response(['code' => 36004004, 'message' => 'Invalid access token'], 401)]);

        try {
            (new KlienTokopedia)->daftarToko();
            $this->fail('Seharusnya galat.');
        } catch (GalatTokopedia $galat) {
            $this->assertSame('Invalid access token (36004004)', $galat->getMessage());
            $this->assertStringNotContainsString('rahasia-uji', $galat->getMessage());
            $this->assertStringNotContainsString('token-uji', $galat->getMessage());
        }
    }

    public function test_balasan_bukan_json_ditolak(): void
    {
        Http::fake(['*' => Http::response('<html>gerbang galat</html>', 502)]);

        $this->expectExceptionMessage('tidak bisa dibaca (HTTP 502)');
        (new KlienTokopedia)->daftarToko();
    }
}
