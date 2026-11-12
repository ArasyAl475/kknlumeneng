<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\TokenShopee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KoneksiShopeeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['toko.shopee' => ['partner_id' => '123', 'partner_key' => 'kunci-uji', 'sandbox' => true, 'host' => null]]);
        Http::preventStrayRequests();
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    private function fakeShopeeBerhasil(): void
    {
        Http::fake([
            '*/api/v2/auth/token/get*' => Http::response(['access_token' => 'AKSES-1', 'refresh_token' => 'SEGAR-1', 'expire_in' => 14400, 'error' => '']),
            '*/api/v2/shop/get_shop_info*' => Http::response(['shop_name' => 'Kopi Lumeneng', 'error' => '']),
        ]);
    }

    /** Menekan "Hubungkan" lalu mengambil nonce dari alamat kembali yang dikirim ke Shopee. */
    private function mulaiIzin(): string
    {
        $tujuan = $this->post(route('admin.toko.shopee.hubungkan'))->assertRedirect()->headers->get('Location');
        parse_str((string) parse_url($tujuan, PHP_URL_QUERY), $query);

        return basename(parse_url($query['redirect'], PHP_URL_PATH));
    }

    private function tokenTersimpan(string $akses = 'AKSES-1', string $segar = 'SEGAR-1', int $kedaluwarsaMenit = 240): TokenShopee
    {
        return TokenShopee::query()->create([
            'shop_id' => 777,
            'nama_toko' => 'Kopi Lumeneng',
            'access_token' => $akses,
            'refresh_token' => $segar,
            'akses_kedaluwarsa_pada' => now()->addMinutes($kedaluwarsaMenit),
            'refresh_kedaluwarsa_pada' => now()->addDays(30),
        ]);
    }

    public function test_hubungkan_mengarahkan_ke_halaman_izin_shopee(): void
    {
        $tujuan = $this->post(route('admin.toko.shopee.hubungkan'))->assertRedirect()->headers->get('Location');

        $this->assertStringStartsWith('https://partner.test-stable.shopeemobile.com/api/v2/shop/auth_partner?', $tujuan);
        $this->assertStringContainsString(urlencode(url('/admin/toko/shopee/kembali/')), $tujuan);
    }

    public function test_tanpa_kunci_admin_mendapat_pesan_dan_tidak_dialihkan_ke_shopee(): void
    {
        config(['toko.shopee.partner_id' => null]);

        $this->post(route('admin.toko.shopee.hubungkan'))
            ->assertRedirect(route('admin.toko.marketplace.show', 'shopee'))
            ->assertSessionHas('galat');

        $this->get(route('admin.toko.marketplace.show', 'shopee'))->assertSee('Kunci belum diisi');
    }

    public function test_kembali_dari_shopee_menyimpan_token_terenkripsi(): void
    {
        $this->fakeShopeeBerhasil();
        $nonce = $this->mulaiIzin();

        $this->get(route('admin.toko.shopee.kembali', $nonce).'?code=abc123&shop_id=777')
            ->assertRedirect(route('admin.toko.marketplace.show', 'shopee'))
            ->assertSessionHas('sukses', 'Toko Shopee Kopi Lumeneng berhasil dihubungkan.');

        $token = TokenShopee::query()->sole();
        $this->assertSame(777, $token->shop_id);
        $this->assertSame('AKSES-1', $token->access_token);
        $this->assertSame('SEGAR-1', $token->refresh_token);
        $this->assertSame($this->admin->id, $token->terhubung_oleh);

        // Isi mentah di basis data tidak boleh berupa token asli.
        $mentah = DB::table('toko_shopee_token')->first();
        $this->assertNotSame('AKSES-1', $mentah->access_token);
        $this->assertNotSame('SEGAR-1', $mentah->refresh_token);

        $this->assertDatabaseHas('toko_aktivitas', ['aksi' => 'hubungkan_shopee']);
        $this->get(route('admin.toko.marketplace.show', 'shopee'))
            ->assertSee('Terhubung')->assertSee('Kopi Lumeneng')->assertDontSee('AKSES-1')->assertDontSee('SEGAR-1');
    }

    public function test_nonce_salah_ditolak_tanpa_memanggil_shopee(): void
    {
        $this->mulaiIzin();

        $this->get(route('admin.toko.shopee.kembali', 'nonceTiruan123').'?code=abc123&shop_id=777')
            ->assertRedirect(route('admin.toko.marketplace.show', 'shopee'))
            ->assertSessionHas('galat');

        Http::assertNothingSent();
        $this->assertDatabaseCount('toko_shopee_token', 0);
    }

    public function test_nonce_hanya_bisa_dipakai_sekali(): void
    {
        $this->fakeShopeeBerhasil();
        $nonce = $this->mulaiIzin();
        $alamat = route('admin.toko.shopee.kembali', $nonce).'?code=abc123&shop_id=777';

        $this->get($alamat)->assertSessionHas('sukses');
        $this->get($alamat)->assertSessionHas('galat');
    }

    public function test_nonce_kedaluwarsa_ditolak(): void
    {
        $nonce = $this->mulaiIzin();
        $this->travel(16)->minutes();

        $this->get(route('admin.toko.shopee.kembali', $nonce).'?code=abc123&shop_id=777')->assertSessionHas('galat');
        $this->assertDatabaseCount('toko_shopee_token', 0);
    }

    public function test_kembali_tanpa_kode_tidak_menyimpan_apa_pun(): void
    {
        $nonce = $this->mulaiIzin();

        $this->get(route('admin.toko.shopee.kembali', $nonce).'?shop_id=777')
            ->assertRedirect(route('admin.toko.marketplace.show', 'shopee'))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('toko_shopee_token', 0);
    }

    public function test_shopee_menolak_kode_ditampilkan_sebagai_galat(): void
    {
        Http::fake(['*' => Http::response(['error' => 'error_auth', 'message' => 'Invalid code'], 403)]);
        $nonce = $this->mulaiIzin();

        $this->get(route('admin.toko.shopee.kembali', $nonce).'?code=abc123&shop_id=777')
            ->assertSessionHas('galat', 'Shopee menolak koneksi: Invalid code (error_auth)');

        $this->assertDatabaseCount('toko_shopee_token', 0);
    }

    public function test_nama_toko_gagal_dibaca_koneksi_tetap_tersimpan(): void
    {
        Http::fake([
            '*/api/v2/auth/token/get*' => Http::response(['access_token' => 'AKSES-1', 'refresh_token' => 'SEGAR-1', 'expire_in' => 14400]),
            '*/api/v2/shop/get_shop_info*' => Http::response(['error' => 'error_server', 'message' => 'busy'], 500),
        ]);
        $nonce = $this->mulaiIzin();

        $this->get(route('admin.toko.shopee.kembali', $nonce).'?code=abc123&shop_id=777')
            ->assertSessionHas('sukses', 'Toko Shopee berhasil dihubungkan.');

        $this->assertNull(TokenShopee::query()->sole()->nama_toko);
    }

    public function test_menghubungkan_toko_lain_menggantikan_koneksi_lama(): void
    {
        $this->tokenTersimpan();
        $this->fakeShopeeBerhasil();
        $nonce = $this->mulaiIzin();

        $this->get(route('admin.toko.shopee.kembali', $nonce).'?code=abc123&shop_id=888')->assertSessionHas('sukses');

        $this->assertSame([888], TokenShopee::query()->pluck('shop_id')->all());
    }

    public function test_putuskan_menghapus_token_dan_mencatat_aktivitas(): void
    {
        $this->tokenTersimpan();

        $this->delete(route('admin.toko.shopee.putuskan'))
            ->assertRedirect(route('admin.toko.marketplace.show', 'shopee'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseCount('toko_shopee_token', 0);
        $this->assertDatabaseHas('toko_aktivitas', ['aksi' => 'putuskan_shopee']);
        $this->get(route('admin.toko.marketplace.show', 'shopee'))->assertSee('Belum terhubung');
    }

    public function test_kartu_koneksi_hanya_tampil_di_halaman_shopee(): void
    {
        $this->get(route('admin.toko.marketplace.show', 'tokopedia'))->assertOk()->assertDontSee('Koneksi otomatis Shopee');
    }

    public function test_perintah_memperpanjang_token_yang_hampir_habis(): void
    {
        $this->tokenTersimpan(kedaluwarsaMenit: 30);
        Http::fake(['*/api/v2/auth/access_token/get*' => Http::response(['access_token' => 'AKSES-2', 'refresh_token' => 'SEGAR-2', 'expire_in' => 14400, 'error' => ''])]);

        $this->artisan('toko:perpanjang-token-shopee')->expectsOutputToContain('berhasil diperpanjang')->assertSuccessful();

        $token = TokenShopee::query()->sole();
        $this->assertSame('AKSES-2', $token->access_token);
        $this->assertSame('SEGAR-2', $token->refresh_token);
        $this->assertTrue($token->akses_kedaluwarsa_pada->isAfter(now()->addHours(3)));
        Http::assertSent(fn ($r) => $r['refresh_token'] === 'SEGAR-1' && $r['shop_id'] === 777);
    }

    public function test_perintah_tidak_memanggil_shopee_bila_token_masih_lama(): void
    {
        $this->tokenTersimpan(kedaluwarsaMenit: 200);

        $this->artisan('toko:perpanjang-token-shopee')->expectsOutputToContain('belum perlu')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_perintah_gagal_tidak_mengubah_token_lama(): void
    {
        $this->tokenTersimpan(kedaluwarsaMenit: 30);
        Http::fake(['*' => Http::response(['error' => 'error_auth', 'message' => 'refresh token expired'], 403)]);

        $this->artisan('toko:perpanjang-token-shopee')->assertFailed();

        $this->assertSame('SEGAR-1', TokenShopee::query()->sole()->refresh_token);
    }
}
