<?php

namespace Tests\Feature\Ews;

use App\Services\Desain\DaftarSlot;
use App\Services\Ews\PetaKerawanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetaKerawananTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_ews_memuat_peta_kajian_dan_sumber_tanpa_menu_lipat_lama(): void
    {
        $this->get('/ews')->assertOk()
            ->assertSee('Peta kerawanan longsor desa')
            ->assertSee('bukan status saat ini', false)
            ->assertSee('Peta rawan longsor')
            ->assertDontSee('Ringkasan kerawanan per dukuh')
            ->assertDontSee('Riwayat longsor di Lumeneng')
            ->assertDontSee('Letak dusun')
            ->assertSee('Kajian Pemetaan Bencana Desa Lumeneng 2024');
    }

    public function test_semua_gambar_peta_ada_dan_beralt(): void
    {
        foreach (PetaKerawanan::peta() as $peta) {
            $this->assertFileExists(public_path("images/lumeneng/peta/{$peta['berkas']}.webp"));
            $this->assertNotSame('', $peta['alt']);
        }

        $this->assertNotSame('', PetaKerawanan::bentangAlam()['judul']);
    }

    public function test_beranda_memuat_model_3d_bentang_alam_yang_bisa_diputar(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('data-bentang3d', false)
            ->assertSee('Lihat Lumeneng dari atas')
            ->assertSee('images/lumeneng/beranda-peta-3d.webp', false);
    }

    public function test_foto_bawaan_slot_dan_potensi_semuanya_ada(): void
    {
        foreach (array_keys(DaftarSlot::SEMUA) as $kunci) {
            $this->assertFileExists(public_path(DaftarSlot::bawaan($kunci)), $kunci);
        }
    }
}
