<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\FotoProduk;
use App\Models\Toko\Marketplace;
use App\Models\Toko\Pengaturan;
use App\Models\Toko\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalamanTokoTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_toko_menampilkan_tiga_marketplace_dan_jumlah_produk(): void
    {
        Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/a'])->create();
        Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/b', 'tokopedia' => 'https://www.tokopedia.com/b'])->create();

        $this->get(route('toko.index'))->assertOk()
            ->assertSeeInOrder(['Shopee', '2 produk', 'Tokopedia', '1 produk', 'Facebook', 'Belum ada produk'])
            ->assertSee('bukan situs resmi');
    }

    public function test_marketplace_nonaktif_disembunyikan(): void
    {
        Marketplace::query()->create(['kunci' => 'facebook', 'aktif' => false]);

        // Nama tetap muncul di disclaimer, jadi yang diperiksa adalah kartunya (tautan ke halaman marketplace).
        $this->get(route('toko.index'))->assertOk()->assertDontSee(route('toko.marketplace', 'facebook'));
        $this->get(route('toko.marketplace', 'facebook'))->assertNotFound();
    }

    public function test_daftar_marketplace_hanya_produk_yang_bisa_dibeli_di_sana(): void
    {
        Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/a'])->create(['nama' => 'Kopi Bubuk']);
        Produk::factory()->denganTautan(['tokopedia' => 'https://www.tokopedia.com/b'])->create(['nama' => 'Abon Jamur']);
        Produk::factory()->tersembunyi()->denganTautan(['shopee' => 'https://shopee.co.id/c'])->create(['nama' => 'Bakso Jamur']);

        $this->get(route('toko.marketplace', 'shopee'))->assertOk()
            ->assertSee('Kopi Bubuk')
            ->assertDontSee('Abon Jamur')
            ->assertDontSee('Bakso Jamur');
    }

    public function test_alamat_toko_membuat_semua_produk_tampil(): void
    {
        Marketplace::query()->create(['kunci' => 'shopee', 'url_toko' => 'https://shopee.co.id/tokodesa', 'aktif' => true]);
        $produk = Produk::factory()->create(['nama' => 'Steak Jamur']);

        $this->get(route('toko.marketplace', 'shopee'))->assertOk()->assertSee('Steak Jamur')->assertSee('Buka toko di Shopee');
        $this->get(route('toko.produk', ['shopee', $produk]))->assertOk()->assertSee('Kunjungi toko di Shopee');
    }

    public function test_marketplace_tanpa_produk_menampilkan_keadaan_kosong(): void
    {
        $this->get(route('toko.marketplace', 'facebook'))->assertOk()->assertSee('Belum ada produk di Facebook');
    }

    public function test_detail_produk_tersusun_ala_marketplace(): void
    {
        Pengaturan::simpan(['nama_penjual' => 'BUMDes Lumeneng', 'lokasi_kirim' => 'Paninggaran, Kab. Pekalongan']);
        $produk = Produk::factory()
            ->denganTautan(['shopee' => 'https://shopee.co.id/kopi', 'facebook' => 'https://www.facebook.com/marketplace/item/1/'])
            ->create([
                'nama' => 'Kopi Bubuk',
                'harga' => 25000,
                'deskripsi' => 'Kopi dari kebun warga.',
                'spesifikasi' => [['label' => 'Berat', 'nilai' => '250 g']],
            ]);
        $produk->varian()->create(['nama' => '250 g', 'harga' => 25000, 'tersedia' => true]);
        FotoProduk::query()->create(['produk_id' => $produk->id, 'path' => 'toko/produk/1/a.webp', 'path_kecil' => 'toko/produk/1/a-kecil.webp']);

        $this->get(route('toko.produk', ['shopee', $produk]))->assertOk()
            ->assertSeeInOrder([
                'Foto 1 dari 1: Kopi Bubuk',   // 1. galeri
                'Kopi Bubuk',                  // 2. nama
                'Rp 25.000',                   // 3. harga
                'Pilihan', '250 g',            // 4. varian
                'Tersedia',                    // status stok
                'Beli di Shopee',              // 5. tombol beli
                'Juga tersedia di:', 'Facebook',
                'Pengiriman', 'Dikirim dari Paninggaran, Kab. Pekalongan',
                'Spesifikasi', 'Berat', '250 g',
                'Deskripsi', 'Kopi dari kebun warga.',
                'BUMDes Lumeneng',
            ])
            ->assertSee('property="og:title" content="Kopi Bubuk"', false)
            ->assertSee('property="og:image"', false)
            ->assertDontSee(route('toko.beli', ['tokopedia', $produk]));
    }

    public function test_bagian_yang_kosong_tidak_ditampilkan(): void
    {
        $produk = Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/a'])
            ->create(['nama' => 'Abon Jamur', 'harga' => null, 'deskripsi' => null, 'spesifikasi' => null]);

        // Tabel spesifikasi tetap ada (Stok dan Dikirim Dari selalu terisi, seperti di Shopee),
        // tetapi isian utama yang tidak diisi admin tidak ikut tampil.
        $this->get(route('toko.produk', ['shopee', $produk]))->assertOk()
            ->assertSee('Lihat harga di marketplace')
            ->assertSee('Foto belum tersedia')
            ->assertSee('Spesifikasi Produk')
            ->assertDontSee('Merek')
            ->assertDontSee('Jenis Biji-Bijian')
            ->assertDontSee('Pilihan')
            ->assertDontSee('property="og:image"', false);
    }

    public function test_tabel_spesifikasi_produk_urut_seperti_shopee(): void
    {
        Pengaturan::simpan(['lokasi_kirim' => 'Paninggaran, Kab. Pekalongan']);
        $produk = Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/kopi'])->create([
            'spesifikasi' => [
                ['label' => 'Kategori', 'nilai' => 'Makanan & Minuman'],
                ['label' => 'Merek', 'nilai' => 'Kopi Lumeneng'],
                ['label' => 'Jenis Biji-Bijian', 'nilai' => 'Arabika'],
                ['label' => 'Berat', 'nilai' => '250 g'],
            ],
        ]);

        $this->get(route('toko.produk', ['shopee', $produk]))->assertOk()
            ->assertSeeInOrder([
                'Spesifikasi Produk',
                'Kategori', 'Makanan &amp; Minuman',
                'Stok', 'Tersedia',
                'Merek', 'Kopi Lumeneng',
                'Jenis Biji-Bijian', 'Arabika',
                'Berat', '250 g',
                'Dikirim Dari', 'Paninggaran, Kab. Pekalongan',
            ], false);
    }

    public function test_tabel_spesifikasi_menulis_habis_bila_stok_habis(): void
    {
        $produk = Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/a'])->create(['tersedia' => false]);

        $this->get(route('toko.produk', ['shopee', $produk]))->assertOk()
            ->assertSeeInOrder(['Spesifikasi Produk', 'Stok', 'Habis']);
    }

    public function test_produk_habis_ditandai_dengan_kata(): void
    {
        $produk = Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/a'])->create(['tersedia' => false]);

        $this->get(route('toko.produk', ['shopee', $produk]))->assertOk()->assertSee('Stok habis');
    }

    public function test_produk_tersembunyi_atau_terhapus_tidak_bisa_dibuka(): void
    {
        $tersembunyi = Produk::factory()->tersembunyi()->denganTautan(['shopee' => 'https://shopee.co.id/a'])->create();
        $terhapus = Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/b'])->create();
        $terhapus->delete();

        $this->get(route('toko.produk', ['shopee', $tersembunyi]))->assertNotFound();
        $this->get(route('toko.produk', ['shopee', $terhapus->slug]))->assertNotFound();
        $this->get('/toko/lazada')->assertNotFound();
    }

    public function test_tautan_lama_tetap_terbuka_dan_menawarkan_marketplace_lain(): void
    {
        $produk = Produk::factory()->denganTautan(['tokopedia' => 'https://www.tokopedia.com/a'])->create();

        $this->get(route('toko.produk', ['shopee', $produk]))->assertOk()
            ->assertSee('Produk ini belum tersedia di Shopee')
            ->assertSee('Tersedia di:');
    }

    public function test_menu_landing_muncul_saat_modul_dinyalakan(): void
    {
        config(['modul.toko.aktif' => true]);

        $this->get(route('landing'))->assertOk()->assertSee(route('toko.index'));
    }
}
