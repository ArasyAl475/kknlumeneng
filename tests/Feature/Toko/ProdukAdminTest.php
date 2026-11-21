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
                ['label' => 'Merek', 'nilai' => 'Kopi Lumeneng'],
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
        $this->assertSame([['label' => 'Merek', 'nilai' => 'Kopi Lumeneng']], $produk->spesifikasi);
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

    public function test_spesifikasi_bebas_tersimpan_sesuai_urutan_admin_dan_yang_kosong_dibuang(): void
    {
        $this->post(route('admin.toko.produk.store'), $this->isian([
            'spesifikasi' => [
                ['label' => 'Kategori', 'nilai' => 'Makanan & Minuman'],
                ['label' => ' Berat Bersih ', 'nilai' => '  250 g '],
                ['label' => 'Merek', 'nilai' => ''],          // baris contoh yang tidak diisi
                ['label' => '', 'nilai' => ''],
                ['label' => 'Cara Seduh', 'nilai' => 'Tubruk'],
            ],
        ]))->assertSessionHasNoErrors();

        $this->assertSame([
            ['label' => 'Kategori', 'nilai' => 'Makanan & Minuman'],
            ['label' => 'Berat Bersih', 'nilai' => '250 g'],
            ['label' => 'Cara Seduh', 'nilai' => 'Tubruk'],
        ], Produk::query()->sole()->spesifikasi);
    }

    public function test_menyimpan_ulang_mengganti_seluruh_spesifikasi(): void
    {
        $produk = Produk::factory()->create(['spesifikasi' => [
            ['label' => 'Merek', 'nilai' => 'Lama'],
            ['label' => 'Berat', 'nilai' => '250 g'],
        ]]);

        $this->put(route('admin.toko.produk.update', $produk), $this->isian())->assertSessionHasNoErrors();

        $this->assertSame([['label' => 'Merek', 'nilai' => 'Kopi Lumeneng']], $produk->refresh()->spesifikasi);
    }

    public function test_spesifikasi_terlalu_panjang_ditolak(): void
    {
        $this->post(route('admin.toko.produk.store'), $this->isian(['spesifikasi' => [
            ['label' => str_repeat('a', 61), 'nilai' => 'x'],
            ['label' => 'Merek', 'nilai' => str_repeat('a', 201)],
        ]]))->assertSessionHasErrors(['spesifikasi.0.label', 'spesifikasi.1.nilai']);
    }

    public function test_isi_tanpa_judul_ditolak_dengan_pesan_jelas(): void
    {
        $this->post(route('admin.toko.produk.store'), $this->isian(['spesifikasi' => [3 => ['label' => '', 'nilai' => '250 g']]]))
            ->assertSessionHasErrors(['spesifikasi.3.label' => 'Baris spesifikasi yang diberi isi juga perlu diberi judul, misalnya "Berat".']);
    }

    public function test_judul_stok_dan_dikirim_dari_ditolak_karena_otomatis(): void
    {
        $this->post(route('admin.toko.produk.store'), $this->isian(['spesifikasi' => [
            ['label' => 'stok', 'nilai' => 'Banyak'],
            ['label' => 'Dikirim Dari', 'nilai' => 'Jakarta'],
        ]]))->assertSessionHasErrors(['spesifikasi.0.label', 'spesifikasi.1.label']);

        $this->assertSame(0, Produk::query()->count());
    }

    public function test_batas_baris_hanya_menghitung_baris_berisi(): void
    {
        $berisi = array_map(fn (int $n) => ['label' => "Judul {$n}", 'nilai' => 'isi'], range(1, 20));
        $kosong = array_map(fn (int $n) => ['label' => "Contoh {$n}", 'nilai' => ''], range(1, 7));

        $this->post(route('admin.toko.produk.store'), ['spesifikasi' => [...$berisi, ...$kosong]] + $this->isian(['spesifikasi' => []]))
            ->assertSessionHasNoErrors();
        $this->assertCount(20, Produk::query()->sole()->spesifikasi);

        $this->post(route('admin.toko.produk.store'), ['spesifikasi' => [...$berisi, ['label' => 'Ke-21', 'nilai' => 'isi']]] + $this->isian(['nama' => 'Lain']))
            ->assertSessionHasErrors('spesifikasi');
    }

    public function test_form_tambah_menyiapkan_judul_contoh_yang_bisa_diganti(): void
    {
        $respons = $this->get(route('admin.toko.produk.create'))->assertOk()
            ->assertSeeInOrder([
                'name="spesifikasi[0][label]"', 'value="Kategori"',
                'name="spesifikasi[6][label]"', 'value="Spesies Tanaman"',
                '<datalist id="saran-spesifikasi">',
            ], false)
            ->assertSee('Tambah baris spesifikasi');

        // 7 judul contoh + 1 baris kosong.
        $this->assertSame(8, $this->jumlahBarisSpesifikasi($respons->getContent()));
    }

    private function jumlahBarisSpesifikasi(string $html): int
    {
        return preg_match_all('/name="spesifikasi\[\d+\]\[label\]"/', $html);
    }

    public function test_form_ubah_menampilkan_semua_baris_tersimpan(): void
    {
        $produk = Produk::factory()->create(['spesifikasi' => [
            ['label' => 'merek', 'nilai' => 'Kopi Lumeneng'],
            ['label' => 'Berat', 'nilai' => '250 g'],
        ]]);

        $respons = $this->get(route('admin.toko.produk.edit', $produk))->assertOk()
            ->assertSeeInOrder(['value="merek"', 'value="Kopi Lumeneng"', 'value="Berat"', 'value="250 g"'], false);

        // Produk yang sudah punya spesifikasi tidak diberi judul contoh lagi: 2 baris tersimpan + 1 baris kosong.
        $this->assertSame(3, $this->jumlahBarisSpesifikasi($respons->getContent()));
    }

    public function test_hanya_nama_yang_wajib_diisi(): void
    {
        $this->post(route('admin.toko.produk.store'), $this->isian(['nama' => '', 'harga' => '']))
            ->assertSessionHasErrors(['nama' => 'Nama produk wajib diisi.'])
            ->assertSessionDoesntHaveErrors('harga');

        $this->assertSame(0, Produk::query()->count());
    }

    public function test_produk_boleh_tanpa_harga(): void
    {
        $this->post(route('admin.toko.produk.store'), ['nama' => 'Jamur Segar', 'harga' => '', 'tersedia' => '1', 'tampil' => '1'])
            ->assertSessionHasNoErrors();

        $produk = Produk::query()->sole();
        $this->assertNull($produk->harga);
        $this->assertNull($produk->hargaTeks());
    }

    public function test_harga_nol_ditolak(): void
    {
        $this->post(route('admin.toko.produk.store'), $this->isian(['harga' => '0']))
            ->assertSessionHasErrors(['harga' => 'Harga harus lebih dari 0. Kosongkan saja bila harga hanya dilihat di marketplace.']);
    }

    public function test_alert_galat_dan_penanda_wajib_tampil_di_formulir(): void
    {
        $this->get(route('admin.toko.produk.create'))
            ->assertOk()
            ->assertSee('(wajib)')
            ->assertSee('aria-required="true"', false);

        $this->from(route('admin.toko.produk.create'))
            ->followingRedirects()
            ->post(route('admin.toko.produk.store'), $this->isian(['nama' => '']))
            ->assertSee('role="alert"', false)
            ->assertSee('Periksa kembali isian berikut')
            ->assertSee('Nama produk wajib diisi')
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
