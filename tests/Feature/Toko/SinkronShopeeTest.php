<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\Produk;
use App\Models\Toko\TautanProduk;
use App\Models\Toko\TokenShopee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SinkronShopeeTest extends TestCase
{
    use RefreshDatabase;

    private const DAFTAR = '*/api/v2/product/get_item_list*';

    private const RINCIAN = '*/api/v2/product/get_item_base_info*';

    protected function setUp(): void
    {
        parent::setUp();
        config(['toko.shopee' => ['partner_id' => '123', 'partner_key' => 'kunci-uji', 'sandbox' => true, 'host' => null]]);
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->admin()->create());
    }

    private function token(int $refreshHari = 30): TokenShopee
    {
        return TokenShopee::query()->create([
            'shop_id' => 777,
            'nama_toko' => 'Kopi Lumeneng',
            'access_token' => 'AKSES-RAHASIA',
            'refresh_token' => 'SEGAR-RAHASIA',
            'akses_kedaluwarsa_pada' => now()->addHours(3),
            'refresh_kedaluwarsa_pada' => now()->addDays($refreshHari),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $item  rincian item seperti balasan get_item_base_info
     */
    private function fakeShopee(array $item): void
    {
        Http::fake([
            self::DAFTAR => Http::response(['error' => '', 'response' => [
                'item' => array_map(fn ($i) => ['item_id' => $i['item_id'], 'item_status' => 'NORMAL'], $item),
                'has_next_page' => false,
            ]]),
            self::RINCIAN => Http::response(['error' => '', 'response' => ['item_list' => $item]]),
        ]);
    }

    /** @return array<string, mixed> */
    private function item(int $id, string $nama = 'Kopi Robusta 250 gram', ?float $harga = 35000, string $deskripsi = 'Kopi petik merah.'): array
    {
        return [
            'item_id' => $id,
            'item_name' => $nama,
            'description' => $deskripsi,
            'description_type' => 'normal',
            'price_info' => $harga === null ? [] : [['current_price' => $harga, 'currency' => 'IDR']],
        ];
    }

    public function test_produk_baru_dari_shopee_dibuat_dan_langsung_tampil(): void
    {
        $this->token();
        $this->fakeShopee([$this->item(1001)]);

        $this->post(route('admin.toko.shopee.sinkron'))->assertRedirect(route('admin.toko.marketplace.show', 'shopee'))->assertSessionHas('sukses');

        $produk = Produk::query()->firstOrFail();
        $this->assertSame('Kopi Robusta 250 gram', $produk->nama);
        $this->assertSame(35000, $produk->harga);
        $this->assertSame('Kopi petik merah.', $produk->deskripsi);
        $this->assertTrue($produk->tampil);
        $this->assertSame('kopi-robusta-250-gram', $produk->slug);

        $tautan = $produk->tautan()->firstOrFail();
        $this->assertSame('1001', $tautan->id_eksternal);
        $this->assertSame(TautanProduk::SUMBER_API, $tautan->sumber);
        $this->assertSame('https://shopee.co.id/product/777/1001', $tautan->url);
        $this->assertNotNull($tautan->terakhir_sinkron);
    }

    public function test_produk_lama_ditimpa_data_shopee_tanpa_mengubah_slug_dan_status_tampil(): void
    {
        $this->token();
        $produk = Produk::factory()->tersembunyi()->denganTautan(['shopee' => 'https://shopee.co.id/Kopi-Lama-i.777.1001'])
            ->create(['nama' => 'Nama Lama', 'slug' => 'nama-lama', 'harga' => 10000, 'deskripsi' => 'Lama.']);
        $this->fakeShopee([$this->item(1001, 'Kopi Baru', 42000, 'Deskripsi baru.')]);

        $this->post(route('admin.toko.shopee.sinkron'))->assertSessionHas('sukses');

        $produk->refresh();
        $this->assertSame(1, Produk::query()->count());
        $this->assertSame('Kopi Baru', $produk->nama);
        $this->assertSame(42000, $produk->harga);
        $this->assertSame('Deskripsi baru.', $produk->deskripsi);
        $this->assertSame('nama-lama', $produk->slug);
        $this->assertFalse($produk->tampil);

        // Alamat buatan admin dipertahankan; hanya penanda sinkron yang diisi.
        $tautan = $produk->tautan()->firstOrFail();
        $this->assertSame('https://shopee.co.id/Kopi-Lama-i.777.1001', $tautan->url);
        $this->assertSame('1001', $tautan->id_eksternal);
        $this->assertSame(TautanProduk::SUMBER_MANUAL, $tautan->sumber);
    }

    public function test_sinkron_kedua_tidak_membuat_produk_ganda(): void
    {
        $this->token();
        $this->fakeShopee([$this->item(1001)]);

        $this->post(route('admin.toko.shopee.sinkron'));
        $this->post(route('admin.toko.shopee.sinkron'));

        $this->assertSame(1, Produk::query()->count());
        $this->assertSame(1, TautanProduk::query()->count());
    }

    public function test_produk_yang_dihapus_admin_tidak_dihidupkan_lagi(): void
    {
        $this->token();
        $produk = Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/product/777/1001'])->create();
        $produk->delete();
        $this->fakeShopee([$this->item(1001)]);

        $this->post(route('admin.toko.shopee.sinkron'));

        $this->assertSame(0, Produk::query()->count());
        $this->assertSame(1, Produk::onlyTrashed()->count());
    }

    public function test_harga_lama_dijaga_bila_shopee_tidak_mengirim_harga(): void
    {
        $this->token();
        $produk = Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/product/777/1001'])->create(['harga' => 25000]);
        $this->fakeShopee([$this->item(1001, 'Kopi Bervarian', null)]);

        $this->post(route('admin.toko.shopee.sinkron'));

        $this->assertSame(25000, $produk->refresh()->harga);
        $this->assertSame('Kopi Bervarian', $produk->nama);
    }

    public function test_deskripsi_extended_diambil_teksnya_saja(): void
    {
        $this->token();
        $item = $this->item(1001) + [];
        $item['description'] = '';
        $item['description_type'] = 'extended';
        $item['extended_description'] = ['field_list' => [
            ['field_type' => 'text', 'text' => 'Paragraf satu.'],
            ['field_type' => 'image', 'image_info' => ['image_id' => 'x']],
            ['field_type' => 'text', 'text' => 'Paragraf dua.'],
        ]];
        $this->fakeShopee([$item]);

        $this->post(route('admin.toko.shopee.sinkron'));

        $this->assertSame("Paragraf satu.\n\nParagraf dua.", Produk::query()->firstOrFail()->deskripsi);
    }

    public function test_item_tanpa_nama_dihitung_gagal_dan_tidak_menghentikan_item_lain(): void
    {
        $this->token();
        $this->fakeShopee([$this->item(1001, ''), $this->item(1002, 'Jamur Tiram')]);

        $this->post(route('admin.toko.shopee.sinkron'))->assertSessionHas('sukses', fn ($pesan) => str_contains($pesan, '1 produk baru') && str_contains($pesan, '1 gagal'));

        $this->assertSame(['Jamur Tiram'], Produk::query()->pluck('nama')->all());
    }

    public function test_galat_dari_shopee_ditampilkan_dan_data_tidak_berubah(): void
    {
        $this->token();
        $produk = Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/product/777/1001'])->create(['nama' => 'Tetap']);
        Http::fake([self::DAFTAR => Http::response(['error' => 'error_auth', 'message' => 'Invalid access_token'])]);

        $this->post(route('admin.toko.shopee.sinkron'))->assertSessionHas('galat');

        $this->assertSame('Tetap', $produk->refresh()->nama);
    }

    public function test_belum_terhubung_memberi_pesan_ramah_tanpa_memanggil_shopee(): void
    {
        $this->post(route('admin.toko.shopee.sinkron'))
            ->assertRedirect(route('admin.toko.marketplace.show', 'shopee'))
            ->assertSessionHas('galat', fn ($pesan) => str_contains($pesan, 'belum terhubung'));

        Http::assertNothingSent();
    }

    public function test_izin_yang_sudah_habis_ditolak(): void
    {
        $this->token(refreshHari: -1);

        $this->post(route('admin.toko.shopee.sinkron'))->assertSessionHas('galat', fn ($pesan) => str_contains($pesan, 'Hubungkan ulang'));

        Http::assertNothingSent();
    }

    public function test_tamu_tidak_bisa_menyinkron(): void
    {
        $this->token();
        auth()->logout();

        $this->post(route('admin.toko.shopee.sinkron'))->assertRedirect();

        Http::assertNothingSent();
        $this->assertSame(0, Produk::query()->count());
    }

    public function test_token_tidak_masuk_log_saat_sinkron_gagal(): void
    {
        $this->token();
        Http::fake([self::DAFTAR => Http::response(['error' => 'error_server', 'message' => 'Gangguan'])]);
        $berkas = storage_path('logs/laravel.log');
        $awal = is_file($berkas) ? filesize($berkas) : 0;

        $this->artisan('toko:sinkron-shopee')->assertFailed();
        // Lewat tombol admin juga: jalur ini dulu memakai report() yang mencetak argumen fungsi.
        $this->post(route('admin.toko.shopee.sinkron'))->assertSessionHas('galat');

        // Hanya log yang ditulis selama tes ini, supaya sisa log lama tidak ikut dihitung.
        clearstatcache();
        $isiLog = is_file($berkas) ? (string) file_get_contents($berkas, false, null, $awal) : '';
        $this->assertStringContainsString('Sinkron produk Shopee gagal', $isiLog);
        $this->assertStringNotContainsString('AKSES-RAHASIA', $isiLog);
        $this->assertStringNotContainsString('SEGAR-RAHASIA', $isiLog);
    }

    public function test_perintah_artisan_menyinkron_produk(): void
    {
        $this->token();
        $this->fakeShopee([$this->item(1001)]);

        $this->artisan('toko:sinkron-shopee')->expectsOutputToContain('Dibuat 1')->assertSuccessful();

        $this->assertSame(1, Produk::query()->count());
    }
}
