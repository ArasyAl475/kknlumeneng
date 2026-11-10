<?php

namespace Tests\Feature\Fondasi;

use App\Models\Ews\Pembacaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    /**
     * @return array<int, string>
     */
    private function dropdownTerbuka(string $html): array
    {
        preg_match_all('/<details data-menu="([a-z]+)" name="menu-admin-layar"[^>]*\bopen\b/', $html, $cocok);

        return $cocok[1];
    }

    public function test_setiap_modul_menjadi_dropdown_berurutan(): void
    {
        $html = $this->get(route('admin.beranda'))->assertOk()->getContent();

        foreach (['profil', 'berita', 'ews', 'toko', 'panen', 'edukasi'] as $kunci) {
            // Satu untuk menu HP, satu untuk menu layar lebar.
            $this->assertSame(2, substr_count($html, '<details data-menu="'.$kunci.'"'), $kunci);
        }

        $this->get(route('admin.beranda'))->assertSeeInOrder(['Profil Desa', 'Berita &amp; Galeri', 'Peringatan Dini', 'Toko Desa', 'Panen &amp; Limbah', 'Edukasi'], false);
    }

    public function test_semua_dropdown_tertutup_di_beranda_admin(): void
    {
        $this->assertSame([], $this->dropdownTerbuka($this->get(route('admin.beranda'))->getContent()));
    }

    public function test_hanya_dropdown_modul_yang_sedang_dibuka_yang_terbuka(): void
    {
        $halaman = [
            'admin.profil.kontak.edit' => 'profil',
            'admin.profil.berita.index' => 'berita',
            'admin.profil.galeri.create' => 'berita',
            'admin.ews.alat' => 'ews',
            'admin.toko.panen-kopi.index' => 'panen',
            'admin.edukasi.kategori.index' => 'edukasi',
        ];

        foreach ($halaman as $rute => $kunci) {
            $this->assertSame([$kunci], $this->dropdownTerbuka($this->get(route($rute))->assertOk()->getContent()), $rute);
        }
    }

    public function test_menu_hp_dan_layar_lebar_memakai_nama_kelompok_berbeda(): void
    {
        // Nama sama di dua menu akan membuat browser menutup salah satu dropdown yang aktif.
        $html = $this->get(route('admin.profil.kontak.edit'))->getContent();

        $this->assertStringContainsString('name="menu-admin-hp"', $html);
        $this->assertStringContainsString('name="menu-admin-layar"', $html);
    }

    public function test_jumlah_validasi_ews_tampil_di_judul_dropdown(): void
    {
        Pembacaan::factory()->create(['validasi' => Pembacaan::VALIDASI_MENUNGGU]);

        $html = $this->get(route('admin.beranda'))->getContent();

        $this->assertMatchesRegularExpression('#<summary[^>]*>.*?Peringatan Dini</span>\s*<span class="menu-admin__lencana">1#s', $html);
    }
}
