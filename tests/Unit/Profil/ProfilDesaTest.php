<?php

namespace Tests\Unit\Profil;

use App\Models\Profil\Berita;
use App\Models\Profil\Pengaturan;
use App\Services\Profil\ProfilDesa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilDesaTest extends TestCase
{
    use RefreshDatabase;

    public function test_misi_membuang_nomor_dan_baris_kosong(): void
    {
        Pengaturan::simpan(['misi' => "1. Satu\n2) Dua\n\n- Tiga\n• Empat\n   Lima  "]);

        $this->assertSame(['Satu', 'Dua', 'Tiga', 'Empat', 'Lima'], ProfilDesa::muat()->misi());
    }

    public function test_angka_ribuan_dan_desimal_gaya_indonesia(): void
    {
        Pengaturan::simpan(['jumlah_penduduk' => '1234567', 'luas_wilayah' => '642.5', 'ketinggian' => '']);
        $profil = ProfilDesa::muat();

        $this->assertSame('1.234.567', $profil->angka('jumlah_penduduk'));
        $this->assertSame('642,5', $profil->angka('luas_wilayah'));
        $this->assertNull($profil->angka('ketinggian'));
    }

    public function test_statistik_hanya_memuat_yang_terisi(): void
    {
        Pengaturan::simpan(['jumlah_penduduk' => '3412', 'jumlah_rt' => '24']);

        $label = array_column(ProfilDesa::muat()->statistik(), 'label');

        $this->assertSame(['Penduduk', 'Rukun Tetangga'], $label);
    }

    public function test_isian_kaya_teks_lama_menjadi_paragraf(): void
    {
        Pengaturan::simpan(['sejarah' => "Satu\nmasih satu\n\n\n  \nDua"]);

        $this->assertSame('<p>Satu<br />masih satu</p><p>Dua</p>', (string) ProfilDesa::muat()->isiKaya('sejarah'));
    }

    public function test_telepon_dan_peta_aman_untuk_tautan(): void
    {
        Pengaturan::simpan(['telepon' => '(0285) 123-456', 'tautan_peta' => 'javascript:alert(1)']);
        $profil = ProfilDesa::muat();

        $this->assertSame('0285123456', $profil->teleponTautan());
        $this->assertNull($profil->tautanPeta());
    }

    public function test_lokasi_melewati_isian_kosong(): void
    {
        Pengaturan::simpan(['kecamatan' => '', 'kabupaten' => 'Pekalongan']);

        $this->assertSame('Kab. Pekalongan, Jawa Tengah', ProfilDesa::muat()->lokasi());
    }

    public function test_menit_baca_tidak_menghitung_tag_html(): void
    {
        $berita = new Berita(['isi' => '<p><strong>Pembuka</strong></p><p>'.str_repeat('kata ', 400).'</p>']);

        $this->assertSame(3, $berita->menitBaca());
    }
}
