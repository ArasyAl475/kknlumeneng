<?php

namespace Tests\Feature\Landing;

use App\Models\Ews\Pembacaan;
use App\Models\Ews\Wilayah;
use App\Services\Landing\RingkasanEws;
use App\Support\Modul;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BerandaTest extends TestCase
{
    use RefreshDatabase;

    public function test_menampilkan_bagian_utama_dan_meta(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Tentang program')
            ->assertSee('Karang Taruna Giri')
            ->assertSee('Petani Muda Jaya')
            ->assertSee(__('landing.deskripsi_meta'))
            ->assertSee('favicon.svg');
    }

    public function test_modul_mati_tampil_segera_hadir_tanpa_tautan(): void
    {
        config(['modul.toko.aktif' => false]);

        $this->get('/')->assertSee('Toko Desa')->assertSee('Segera hadir')->assertDontSee('/toko"', false);
    }

    public function test_kartu_catat_limbah_membuka_halaman_hasil_panen_saat_toko_menyala(): void
    {
        config(['modul.limbah.aktif' => true]);

        $this->get('/')->assertSee('History Limbah')->assertSee(route('toko.hasil'), false);
    }

    public function test_menu_atas_memuat_toko_edukasi_dan_history_limbah(): void
    {
        // Dipaksa menyala agar tes tidak bergantung pada isi .env di komputer masing-masing.
        config(['modul.profil.aktif' => true, 'modul.berita.aktif' => true, 'modul.galeri.aktif' => true, 'modul.ews.aktif' => true, 'modul.toko.aktif' => true, 'modul.edukasi.aktif' => true, 'modul.limbah.aktif' => true]);

        $menu = Modul::tersedia();

        $this->assertSame(['profil', 'berita', 'galeri', 'ews', 'toko', 'edukasi', 'limbah'], array_keys($menu));

        $html = $this->get('/')->assertOk()->getContent();
        foreach (['toko.index', 'edukasi.index', 'toko.hasil'] as $rute) {
            $this->assertStringContainsString('href="'.route($rute).'"', $html);
        }
        $this->assertStringNotContainsString('Segera hadir', $html);
    }

    public function test_menu_atas_tidak_memuat_limbah_saat_toko_mati(): void
    {
        config(['modul.toko.aktif' => false, 'modul.limbah.aktif' => false]);

        $this->assertArrayNotHasKey('toko', Modul::tersedia());
        $this->assertArrayNotHasKey('limbah', Modul::tersedia());
    }

    public function test_kartu_catat_limbah_segera_hadir_saat_toko_mati(): void
    {
        config(['modul.limbah.aktif' => false]);

        $this->get('/')->assertSee('History Limbah')->assertDontSee(route('toko.hasil'), false);
    }

    public function test_tanpa_data_menampilkan_keadaan_belum_ada_data(): void
    {
        $this->get('/')->assertSee('Belum ada data dari alat sensor.');
    }

    public function test_modul_ews_mati_tidak_memuat_status(): void
    {
        config(['modul.ews.aktif' => false]);

        $this->assertNull(app(RingkasanEws::class)->ambil());
        $this->get('/')->assertOk()->assertDontSee('Lihat Status Peringatan Dini');
    }

    public function test_nomor_darurat_tidak_dikarang(): void
    {
        $this->get('/')->assertSee('Nomor resmi sedang diverifikasi')->assertDontSee('href="tel:', false);
    }

    public function test_tidak_memuat_skrip_atau_gaya_dari_cdn(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertDoesNotMatchRegularExpression('/<(script|link)[^>]+(src|href)="https?:\/\/(?!localhost)/i', $html);
    }

    public function test_status_beranda_siap_diperbarui_otomatis(): void
    {
        config(['modul.ews.aktif' => true]);
        Pembacaan::factory()->create();

        $html = $this->get('/')->assertOk()
            ->assertSee('id="status-langsung"', false)
            ->assertSee('data-url-data="'.route('ews.data').'"', false)
            ->assertSee('id="chip-status"', false)
            ->assertSee('id=panel-status', false)
            ->assertSee('id="pengumuman"', false)
            ->getContent();

        // Kalimat saran yang dikirim ke skrip harus sama dengan yang tampil saat halaman dimuat.
        $this->assertStringContainsString('Kondisi lereng terpantau normal.', html_entity_decode($html));
    }

    public function test_status_beranda_tanpa_modul_ews_tidak_memuat_pembaruan(): void
    {
        config(['modul.ews.aktif' => false]);

        $this->get('/')->assertOk()->assertDontSee('id="status-langsung"', false);
    }

    public function test_ringkasan_memakai_status_terburuk_ews(): void
    {
        Pembacaan::factory()->create(['wilayah_id' => Wilayah::factory()->awas()->create(['nama' => 'Lereng Uji'])]);

        $this->get('/')->assertSee('Awas')->assertSee('Lereng Uji')->assertSee('Lihat Status Peringatan Dini')->assertSee(route('ews.index'), false);
    }
}
