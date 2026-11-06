<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\Marketplace;
use App\Models\Toko\Pengaturan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_admin_menyimpan_alamat_toko_dan_dasbor(): void
    {
        $this->put(route('admin.toko.marketplace.update', 'shopee'), [
            'url_toko' => 'https://shopee.co.id/tokodesalumeneng',
            'url_dasbor' => 'https://seller.shopee.co.id/',
            'aktif' => '1',
        ])->assertRedirect(route('admin.toko.marketplace.show', 'shopee'));

        $shopee = Marketplace::untuk('shopee');
        $this->assertSame('https://shopee.co.id/tokodesalumeneng', $shopee->url_toko);
        $this->assertTrue($shopee->aktif);
        $this->assertDatabaseHas('toko_aktivitas', ['aksi' => 'ubah_marketplace']);

        $this->get(route('admin.toko.marketplace.show', 'shopee'))->assertSee('Buka dasbor penjual Shopee');
    }

    public function test_alamat_dari_marketplace_lain_ditolak(): void
    {
        $this->put(route('admin.toko.marketplace.update', 'tokopedia'), [
            'url_toko' => 'https://shopee.co.id/tokodesalumeneng',
            'aktif' => '1',
        ])->assertSessionHasErrors('url_toko');

        $this->assertNull(Marketplace::untuk('tokopedia')->url_toko);
    }

    public function test_marketplace_tidak_dikenal_tidak_ada(): void
    {
        $this->get('/admin/toko/marketplace/lazada')->assertNotFound();
    }

    public function test_info_penjual_bisa_diubah(): void
    {
        $this->put(route('admin.toko.pengaturan.update'), [
            'nama_penjual' => 'BUMDes Lumeneng',
            'keterangan_penjual' => '',
            'lokasi_kirim' => 'Desa Lumeneng',
        ])->assertRedirect(route('admin.toko.pengaturan.edit'));

        $pengaturan = Pengaturan::semua();
        $this->assertSame('BUMDes Lumeneng', $pengaturan['nama_penjual']);
        $this->assertSame('Desa Lumeneng', $pengaturan['lokasi_kirim']);
        $this->assertSame('', $pengaturan['keterangan_penjual']);
    }
}
