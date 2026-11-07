<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\Aktivitas;
use App\Models\Toko\Marketplace;
use App\Models\Toko\Produk;
use App\Models\User;
use Database\Seeders\TokoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdukAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    /**
     * @return array<string, mixed>
     */
    private function isian(array $timpa = []): array
    {
        return array_replace_recursive([
            'nama' => 'Kopi Bubuk',
            'deskripsi' => "Kopi dari kebun warga.\nDiolah sendiri.",
            'harga' => 'Rp 25.000',
            'urutan' => '1',
            'tersedia' => '1',
            'tampil' => '1',
            'tautan' => [
                'shopee' => 'https://shopee.co.id/kopi-bubuk-i.1.2',
                'tokopedia' => '',
                'facebook' => 'https://www.facebook.com/marketplace/item/123/',
            ],
            'varian' => [
                ['nama' => '100 g', 'harga' => '15.000', 'tersedia' => '1'],
                ['nama' => '250 g', 'harga' => '30000', 'tersedia' => '0'],
                ['nama' => '', 'harga' => '', 'tersedia' => '1'],
            ],
            'spesifikasi' => [
                ['label' => 'Berat', 'nilai' => '250 g'],
                ['label' => '', 'nilai' => ''],
            ],
        ], $timpa);
    }

    public function test_admin_bisa_menambah_produk_lengkap(): void
    {
        $respons = $this->post(route('admin.toko.produk.store'), $this->isian());

        $produk = Produk::query()->sole();
        $respons->assertRedirect(route('admin.toko.produk.edit', $produk).'#foto');

        $this->assertSame('kopi-bubuk', $produk->slug);
        $this->assertSame(25000, $produk->harga);
        $this->assertSame([['label' => 'Berat', 'nilai' => '250 g']], $produk->spesifikasi);
        $this->assertSame('https://shopee.co.id/kopi-bubuk-i.1.2', $produk->tautanUntuk('shopee'));
        $this->assertNull($produk->tautanUntuk('tokopedia'));
        $this->assertSame(['100 g', '250 g'], $produk->varian->pluck('nama')->all());
        $this->assertSame([15000, 30000], $produk->varian->pluck('harga')->all());
        $this->assertFalse($produk->varian[1]->tersedia);
        $this->assertSame('Rp 15.000 – Rp 30.000', $produk->hargaTeks());
        $this->assertDatabaseHas('toko_aktivitas', ['aksi' => 'tambah_produk']);
    }

    public function test_produk_boleh_tanpa_varian_spesifikasi_dan_tautan(): void
    {
        $this->post(route('admin.toko.produk.store'), [
            'nama' => 'Abon Jamur', 'harga' => '18000', 'tersedia' => '1', 'tampil' => '1',
        ])->assertSessionHasNoErrors();

        $produk = Produk::query()->sole();
        $this->assertSame(18000, $produk->harga);
        $this->assertNull($produk->spesifikasi);
        $this->assertSame('Rp 18.000', $produk->hargaTeks());
    }

    public function test_nama_dan_harga_wajib_diisi_dengan_pesan_yang_jelas(): void
    {
        $this->post(route('admin.toko.produk.store'), $this->isian(['nama' => '', 'harga' => '']))
            ->assertSessionHasErrors([
                'nama' => 'Nama produk wajib diisi.',
                'harga' => 'Harga wajib diisi. Tulis angkanya saja, misalnya 25000.',
            ]);

        $this->assertSame(0, Produk::query()->count());
    }

    public function test_harga_nol_ditolak(): void
    {
        $this->post(route('admin.toko.produk.store'), $this->isian(['harga' => '0']))
            ->assertSessionHasErrors(['harga' => 'Harga harus lebih dari 0.']);
    }

    public function test_alert_galat_dan_penanda_wajib_tampil_di_formulir(): void
    {
        $this->get(route('admin.toko.produk.create'))
            ->assertOk()
            ->assertSee('(wajib)')
            ->assertSee('aria-required="true"', false);

        $this->from(route('admin.toko.produk.create'))
            ->followingRedirects()
            ->post(route('admin.toko.produk.store'), $this->isian(['harga' => '']))
            ->assertSee('role="alert"', false)
            ->assertSee('Periksa kembali isian berikut')
            ->assertSee('Harga wajib diisi')
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_tautan_dari_situs_lain_ditolak(): void
    {
        $this->post(route('admin.toko.produk.store'), $this->isian(['tautan' => [
            'shopee' => 'https://contoh.com/kopi',
            'tokopedia' => 'https://evil-tokopedia.com/kopi',
            'facebook' => 'http://www.facebook.com/marketplace/item/1',
        ]]))->assertSessionHasErrors(['tautan.shopee', 'tautan.tokopedia', 'tautan.facebook']);

        $this->assertSame(0, Produk::query()->count());
    }

    public function test_harga_berisi_huruf_ditolak_bukan_menjadi_nol(): void
    {
        $this->post(route('admin.toko.produk.store'), $this->isian(['harga' => 'dua puluh ribu']))
            ->assertSessionHasErrors('harga');
    }

    public function test_varian_berharga_tanpa_nama_ditolak(): void
    {
        $this->post(route('admin.toko.produk.store'), $this->isian(['varian' => [2 => ['nama' => '', 'harga' => '5000']]]))
            ->assertSessionHasErrors('varian.2.nama');
    }

    public function test_mengubah_nama_tidak_mengubah_slug_dan_tautan_kosong_dihapus(): void
    {
        $produk = Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/a'])->create(['nama' => 'Kopi', 'slug' => 'kopi']);

        $this->put(route('admin.toko.produk.update', $produk), $this->isian([
            'nama' => 'Kopi Robusta',
            'tautan' => ['shopee' => '', 'facebook' => ''],
        ]))->assertRedirect(route('admin.toko.produk.index'));

        $produk->refresh()->load('tautan');
        $this->assertSame('Kopi Robusta', $produk->nama);
        $this->assertSame('kopi', $produk->slug);
        $this->assertTrue($produk->tautan->isEmpty());
        $this->assertSame('Mengubah produk Kopi menjadi Kopi Robusta.', Aktivitas::query()->latest('id')->value('keterangan'));
    }

    public function test_setelah_simpan_kembali_ke_halaman_marketplace_asal(): void
    {
        $produk = Produk::factory()->create();

        $this->put(route('admin.toko.produk.update', $produk), $this->isian(['kembali' => 'tokopedia']))
            ->assertRedirect(route('admin.toko.marketplace.show', 'tokopedia'));
    }

    public function test_slug_ganda_diberi_nomor(): void
    {
        $this->post(route('admin.toko.produk.store'), $this->isian());
        $this->post(route('admin.toko.produk.store'), $this->isian());

        $this->assertSame(['kopi-bubuk', 'kopi-bubuk-2'], Produk::query()->orderBy('id')->pluck('slug')->all());
    }

    public function test_hapus_lalu_pulihkan(): void
    {
        $produk = Produk::factory()->create(['nama' => 'Bakso Jamur']);

        $this->delete(route('admin.toko.produk.destroy', $produk))->assertSessionHas('sukses');
        $this->assertSoftDeleted($produk);

        $this->post(route('admin.toko.produk.pulihkan', $produk->id))->assertSessionHas('sukses');
        $this->assertNotSoftDeleted($produk);
        $this->assertSame(['hapus_produk', 'pulihkan_produk'], Aktivitas::query()->orderBy('id')->pluck('aksi')->all());
    }

    public function test_halaman_admin_terbuka(): void
    {
        $produk = Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/a'])->create(['nama' => 'Steak Jamur']);

        $this->get(route('admin.toko.beranda'))->assertOk()->assertSee('Shopee')->assertSee('1 dari 1 produk punya tautan');
        $this->get(route('admin.toko.marketplace.show', 'shopee'))->assertOk()->assertSee('Steak Jamur')->assertSee('Tautan Shopee terisi');
        $this->get(route('admin.toko.marketplace.show', 'facebook'))->assertOk()->assertSee('Tautan Facebook kosong');
        $this->get(route('admin.toko.produk.index'))->assertOk()->assertSee('Steak Jamur');
        $this->get(route('admin.toko.produk.create'))->assertOk()->assertSee('Foto bisa ditambahkan setelah produk disimpan');
        $this->get(route('admin.toko.produk.edit', $produk))->assertOk()->assertSee('Foto produk (0 dari 5)');
        $this->get(route('admin.toko.pengaturan.edit'))->assertOk()->assertSee('UMKM Desa Lumeneng');
        $this->get(route('admin.toko.aktivitas'))->assertOk();
        $this->get(route('admin.beranda'))->assertOk()->assertSee('Toko Desa');
    }

    // Lazy loading hanya dilarang bila ada lebih dari satu produk, jadi daftar diuji dengan data seeder (5 produk).
    public function test_halaman_daftar_tidak_galat_dengan_banyak_produk(): void
    {
        $this->seed(TokoSeeder::class);
        Marketplace::query()->whereKey('shopee')->update(['url_toko' => 'https://shopee.co.id/tokodesa']);
        Produk::query()->each(fn (Produk $p) => $p->varian()->create(['nama' => '250 g', 'harga' => 25000, 'tersedia' => true]));

        $this->get(route('admin.toko.produk.index'))->assertOk()->assertSee('Rp 25.000');
        $this->get(route('admin.toko.marketplace.show', 'shopee'))->assertOk()->assertSee('Rp 25.000');
        $this->get(route('admin.toko.beranda'))->assertOk();
        $this->get(route('toko.index'))->assertOk();
        $this->get(route('toko.marketplace', 'shopee'))->assertOk()->assertSee('Rp 25.000');
        $this->get(route('toko.produk', ['shopee', 'kopi-bubuk']))->assertOk()->assertSee('Produk lain');
    }
}
