<?php

namespace Tests\Feature\Profil;

use App\Models\Profil\Berita;
use App\Models\Profil\Foto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalamanGaleriTest extends TestCase
{
    use RefreshDatabase;

    public function test_galeri_kosong_ramah(): void
    {
        $this->get(route('galeri.index'))->assertOk()->assertSee('Galeri masih kosong.');
    }

    public function test_foto_dari_berita_draf_tidak_tampil(): void
    {
        $terbit = Berita::factory()->create(['judul' => 'Kegiatan terbit']);
        $draf = Berita::factory()->draf()->create();
        Foto::factory()->create(['judul' => 'Potret sawah']);
        Foto::factory()->create(['judul' => 'Foto kegiatan terbit', 'berita_id' => $terbit->id]);
        $rahasia = Foto::factory()->create(['judul' => 'Foto kegiatan rahasia', 'berita_id' => $draf->id]);

        $this->get(route('galeri.index'))->assertOk()
            ->assertSee('Potret sawah')
            ->assertSee('Foto kegiatan terbit')
            ->assertDontSee('Foto kegiatan rahasia');

        $this->get(route('galeri.show', $rahasia))->assertNotFound();
    }

    public function test_saring_dokumentasi_kegiatan_dan_foto_desa(): void
    {
        $berita = Berita::factory()->create();
        Foto::factory()->create(['judul' => 'Potret sawah']);
        Foto::factory()->create(['judul' => 'Dokumentasi rapat', 'berita_id' => $berita->id]);

        $this->get(route('galeri.index', ['jenis' => 'kegiatan']))->assertOk()->assertSee('Dokumentasi rapat')->assertDontSee('Potret sawah');
        $this->get(route('galeri.index', ['jenis' => 'desa']))->assertOk()->assertSee('Potret sawah')->assertDontSee('Dokumentasi rapat');
        $this->get(route('galeri.index', ['jenis' => 'ngawur']))->assertOk()->assertSee('Potret sawah')->assertSee('Dokumentasi rapat');
    }

    public function test_detail_foto_terhubung_mengambil_info_dan_menjembatani_ke_berita(): void
    {
        $berita = Berita::factory()->create(['judul' => 'Panen raya kopi', 'ringkasan' => 'Warga panen bersama.', 'jenis' => 'berita']);
        $foto = Foto::factory()->create(['berita_id' => $berita->id, 'judul' => null, 'keterangan' => null]);
        Foto::factory()->create(['berita_id' => $berita->id, 'judul' => 'Foto kedua']);

        $this->get(route('galeri.show', $foto))->assertOk()
            // Judul dan keterangan kosong diambil dari berita.
            ->assertSee('Panen raya kopi')
            ->assertSee('Warga panen bersama.')
            ->assertSee('Foto ini dari berita')
            ->assertSee('href="'.route('berita.show', $berita->slug).'"', false)
            ->assertSee('Foto lain dari kegiatan ini')
            ->assertSee($berita->diterbitkan_pada->format('d-m-Y'));
    }

    public function test_detail_foto_mandiri_tanpa_jembatan(): void
    {
        $foto = Foto::factory()->create(['judul' => 'Sawah terasering', 'keterangan' => 'Pagi di lereng.', 'tanggal' => '2026-10-01']);

        $this->get(route('galeri.show', $foto))->assertOk()
            ->assertSee('Sawah terasering')
            ->assertSee('Pagi di lereng.')
            ->assertSee('01-10-2026')
            ->assertSee('Foto desa')
            ->assertDontSee('Baca ceritanya');
    }

    public function test_halaman_berita_menampilkan_dokumentasi_menuju_galeri(): void
    {
        $berita = Berita::factory()->create();
        $foto = Foto::factory()->count(2)->create(['berita_id' => $berita->id]);

        $this->get(route('berita.show', $berita->slug))->assertOk()
            ->assertSee('Dokumentasi kegiatan')
            ->assertSee('2 foto')
            ->assertSee('href="'.route('galeri.show', $foto[0]).'"', false);
    }

    public function test_kotak_foto_membawa_data_untuk_penampil_layar_penuh(): void
    {
        $berita = Berita::factory()->create(['judul' => 'Panen raya', 'jenis' => 'berita']);
        $foto = Foto::factory()->create(['berita_id' => $berita->id, 'judul' => 'Petik kopi', 'keterangan' => 'Pagi hari', 'tanggal' => '2026-10-05']);
        Foto::factory()->create(['judul' => 'Potret bukit']);

        $html = $this->get(route('galeri.index'))->assertOk()
            ->assertSee('<ul data-galeri', false)
            ->getContent();

        $this->assertStringContainsString('data-besar="'.$foto->urlGambar().'"', $html);
        $this->assertStringContainsString('data-judul="Petik kopi"', $html);
        $this->assertStringContainsString('data-tanggal="05-10-2026"', $html);
        $this->assertStringContainsString('data-keterangan="Pagi hari"', $html);
        // Jembatan ke berita ikut dibawa agar penampil bisa menampilkan tombol "Baca cerita".
        $this->assertStringContainsString('data-berita="'.route('berita.show', $berita->slug).'"', $html);
        // Tanpa JavaScript, kotak tetap tautan ke halaman foto.
        $this->assertStringContainsString('href="'.route('galeri.show', $foto).'" data-foto', $html);
    }

    public function test_foto_di_halaman_berita_tidak_menautkan_balik_ke_berita_yang_sama(): void
    {
        $berita = Berita::factory()->create();
        Foto::factory()->create(['berita_id' => $berita->id]);

        $this->get(route('berita.show', $berita->slug))->assertOk()
            ->assertSee('<ul data-galeri', false)
            ->assertDontSee('data-berita=', false);
    }

    public function test_halaman_depan_menampilkan_galeri(): void
    {
        Foto::factory()->create(['judul' => 'Foto untuk beranda']);
        $berita = Berita::factory()->create(['ringkasan' => 'Ringkasan kegiatan beranda']);
        Foto::factory()->create(['berita_id' => $berita->id, 'judul' => null, 'keterangan' => null]);

        $this->get(route('landing'))->assertOk()
            ->assertSee('Galeri desa')
            ->assertSee('Foto untuk beranda')
            ->assertSee('data-keterangan="Ringkasan kegiatan beranda"', false)
            ->assertSee('<ul data-galeri', false);
    }

    public function test_menu_galeri_ada_di_menu_atas(): void
    {
        $this->get(route('landing'))->assertOk()->assertSee('href="'.route('galeri.index').'"', false);
    }
}
