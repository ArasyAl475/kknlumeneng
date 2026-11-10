<?php

namespace Tests\Feature\Profil;

use App\Models\Profil\Berita;
use App\Models\Profil\Foto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalamanBeritaTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_kosong_ramah(): void
    {
        $this->get(route('berita.index'))->assertOk()->assertSee('Belum ada berita.');
    }

    public function test_daftar_hanya_memuat_yang_terbit_urut_terbaru(): void
    {
        Berita::factory()->create(['judul' => 'Kabar kemarin', 'diterbitkan_pada' => now()->subDays(2)]);
        Berita::factory()->create(['judul' => 'Kabar hari ini', 'diterbitkan_pada' => now()->subHour()]);
        Berita::factory()->draf()->create(['judul' => 'Masih draf']);

        $this->get(route('berita.index'))->assertOk()
            ->assertSeeInOrder(['Kabar hari ini', 'Kabar kemarin'])
            ->assertDontSee('Masih draf');
    }

    public function test_saring_jenis_dan_cari(): void
    {
        Berita::factory()->create(['judul' => 'Jadwal posyandu', 'jenis' => 'pengumuman']);
        Berita::factory()->create(['judul' => 'Kerja bakti dusun', 'jenis' => 'berita']);

        $this->get(route('berita.index', ['jenis' => 'pengumuman']))->assertOk()
            ->assertSee('Jadwal posyandu')->assertDontSee('Kerja bakti dusun');

        $this->get(route('berita.index', ['cari' => 'bakti']))->assertOk()
            ->assertSee('Kerja bakti dusun')->assertDontSee('Jadwal posyandu')->assertSee('1 hasil');

        $this->get(route('berita.index', ['cari' => 'tidakada']))->assertOk()->assertSee('Tidak ada berita yang cocok.');
    }

    public function test_jenis_tak_dikenal_tidak_galat(): void
    {
        Berita::factory()->create();

        $this->get(route('berita.index', ['jenis' => 'ngawur']))->assertOk()->assertSee('Tidak ada berita yang cocok.');
    }

    public function test_detail_menampilkan_paragraf_dan_tombol_bagikan(): void
    {
        $berita = Berita::factory()->create([
            'judul' => 'Panen kopi melimpah',
            'isi' => "Paragraf pembuka.\n\nParagraf penutup.",
            'diterbitkan_pada' => now()->setDate(2026, 11, 1)->setTime(9, 30),
        ]);
        Berita::factory()->create(['judul' => 'Kabar lain']);

        $this->get(route('berita.show', $berita->slug))->assertOk()
            ->assertSee('Panen kopi melimpah')
            ->assertSee('01-11-2026 09:30 WIB')
            // Tulisan lama berupa teks biasa tetap tampil sebagai paragraf.
            ->assertSee('<div class="isi-kaya mt-5"><p>Paragraf pembuka.</p><p>Paragraf penutup.</p></div>', false)
            ->assertSee('https://wa.me/?text=', false)
            ->assertSee('Berita lainnya')
            ->assertSee('Kabar lain');
    }

    public function test_draf_menjawab_404(): void
    {
        $berita = Berita::factory()->draf()->create();

        $this->get(route('berita.show', $berita->slug))->assertNotFound();
    }

    public function test_isi_berita_tidak_bisa_menyisipkan_html(): void
    {
        $berita = Berita::factory()->create(['isi' => '<img src=x onerror=alert(1)>']);

        $this->get(route('berita.show', $berita->slug))->assertOk()
            ->assertDontSee('onerror', false);
    }

    public function test_isi_berformat_tampil_dengan_formatnya(): void
    {
        $berita = Berita::factory()->create(['isi' => '<h2>Sub judul</h2><p><strong>Tebal</strong> dan <em>miring</em></p><ul><li>Satu</li></ul><p class="ql-align-center"><span class="ql-size-large">Besar</span></p>']);

        $this->get(route('berita.show', $berita->slug))->assertOk()
            ->assertSee('<h2>Sub judul</h2>', false)
            ->assertSee('<strong>Tebal</strong>', false)
            ->assertSee('<ul><li>Satu</li></ul>', false)
            ->assertSee('<p class="ql-align-center"><span class="ql-size-large">Besar</span></p>', false);
    }

    public function test_kartu_berita_menampilkan_jumlah_foto(): void
    {
        $berita = Berita::factory()->create();
        Foto::factory()->count(3)->create(['berita_id' => $berita->id]);

        $this->get(route('berita.index'))->assertOk()->assertSee('3 foto');
    }

    public function test_menu_atas_menyala_di_halaman_detail(): void
    {
        $berita = Berita::factory()->create();

        $html = $this->get(route('berita.show', $berita->slug))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('berita.index'), '#').'"\s+aria-current="page"#', $html);
    }

    public function test_halaman_depan_menampilkan_kabar_terbaru(): void
    {
        Berita::factory()->create(['judul' => 'Kabar untuk beranda']);

        $this->get(route('landing'))->assertOk()->assertSee('Kabar terbaru dari desa')->assertSee('Kabar untuk beranda');
    }

    public function test_modul_mati_menyembunyikan_menu_dan_kabar(): void
    {
        config(['modul.profil.aktif' => false, 'modul.berita.aktif' => false]);
        Berita::factory()->create(['judul' => 'Kabar tersembunyi']);

        $this->get(route('landing'))->assertOk()
            ->assertDontSee('Kabar tersembunyi')
            ->assertDontSee('href="'.route('berita.index').'"', false);
    }
}
