<?php

namespace Tests\Feature\Toko;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuAdminTokoTest extends TestCase
{
    use RefreshDatabase;

    private const POLA_TERBUKA = '/<details data-menu="toko"[^>]*\bopen\b/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_dropdown_berisi_empat_submenu(): void
    {
        $this->get(route('admin.beranda'))->assertOk()
            ->assertSeeInOrder(['Toko Desa', 'Marketplace', 'Semua produk', 'Info penjual', 'Log aktivitas']);
    }

    public function test_dropdown_terbuka_saat_di_halaman_toko(): void
    {
        $halaman = $this->get(route('admin.toko.produk.index'))->assertOk();

        $this->assertMatchesRegularExpression(self::POLA_TERBUKA, $halaman->getContent());
        $halaman->assertSee('aria-current="page"', false);
    }

    public function test_dropdown_tertutup_di_halaman_lain(): void
    {
        $halaman = $this->get(route('admin.beranda'))->assertOk();

        $this->assertDoesNotMatchRegularExpression(self::POLA_TERBUKA, $halaman->getContent());
    }
}
