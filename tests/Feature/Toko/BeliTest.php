<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\Klik;
use App\Models\Toko\Marketplace;
use App\Models\Toko\Produk;
use App\Models\User;
use Database\Seeders\TokoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeliTest extends TestCase
{
    use RefreshDatabase;

    public function test_klik_dicatat_lalu_dialihkan_ke_tautan_produk(): void
    {
        $produk = Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/kopi-i.1.2'])->create();

        $this->get(route('toko.beli', ['shopee', $produk]))->assertRedirect('https://shopee.co.id/kopi-i.1.2');

        $this->assertDatabaseHas('toko_klik', ['produk_id' => $produk->id, 'marketplace' => 'shopee']);
    }

    public function test_tanpa_tautan_produk_dialihkan_ke_toko(): void
    {
        Marketplace::query()->create(['kunci' => 'tokopedia', 'url_toko' => 'https://www.tokopedia.com/tokodesa', 'aktif' => true]);
        $produk = Produk::factory()->create();

        $this->get(route('toko.beli', ['tokopedia', $produk]))->assertRedirect('https://www.tokopedia.com/tokodesa');
    }

    public function test_tanpa_tujuan_tidak_mengalihkan_ke_mana_pun(): void
    {
        $produk = Produk::factory()->create();

        $this->get(route('toko.beli', ['facebook', $produk]))->assertNotFound();
        $this->get('/toko/lazada/'.$produk->slug.'/beli')->assertNotFound();
        $this->assertSame(0, Klik::query()->count());
    }

    public function test_produk_tersembunyi_tidak_bisa_dibeli(): void
    {
        $produk = Produk::factory()->tersembunyi()->denganTautan(['shopee' => 'https://shopee.co.id/a'])->create();

        $this->get(route('toko.beli', ['shopee', $produk]))->assertNotFound();
    }

    public function test_jumlah_klik_tampil_di_beranda_admin(): void
    {
        $produk = Produk::factory()->denganTautan(['shopee' => 'https://shopee.co.id/a'])->create();
        $this->get(route('toko.beli', ['shopee', $produk]));
        $this->get(route('toko.beli', ['shopee', $produk]));

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.toko.beranda'))->assertSee('2 klik “Beli” dalam 30 hari terakhir');
    }

    public function test_seeder_membuat_lima_produk_dan_aman_diulang(): void
    {
        $this->seed(TokoSeeder::class);
        $this->seed(TokoSeeder::class);

        $this->assertSame(
            ['Kopi Bubuk', 'Baglog Jamur Tiram', 'Bakso Jamur', 'Steak Jamur', 'Abon Jamur'],
            Produk::query()->orderBy('urutan')->pluck('nama')->all(),
        );
        $this->assertSame(3, Marketplace::query()->count());
        $this->assertNull(Produk::query()->whereNotNull('harga')->first(), 'Seeder tidak boleh mengarang harga.');
    }
}
