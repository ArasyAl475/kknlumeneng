<?php

namespace Tests\Feature\Edukasi;

use App\Models\Edukasi\Kategori;
use App\Models\Edukasi\Langkah;
use App\Models\Edukasi\Materi;
use App\Support\Modul;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalamanPublikTest extends TestCase
{
    use RefreshDatabase;

    public function test_keadaan_kosong_saat_belum_ada_materi(): void
    {
        $this->get('/edukasi')->assertOk()->assertSee('Materi belum tersedia')->assertDontSee('Cari materi');
    }

    public function test_draf_tidak_tampil_di_daftar_dan_detail(): void
    {
        $draf = Materi::factory()->create(['judul' => 'Rahasia Draf']);
        Materi::factory()->terbit()->create(['judul' => 'Sudah Terbit']);

        $this->get('/edukasi')->assertOk()->assertSee('Sudah Terbit')->assertDontSee('Rahasia Draf');
        $this->get("/edukasi/{$draf->slug}")->assertNotFound();
    }

    public function test_kategori_tanpa_materi_terbit_tidak_ditawarkan(): void
    {
        Materi::factory()->terbit()->for(Kategori::factory()->state(['nama' => 'Kategori Berisi']))->create();
        Materi::factory()->for(Kategori::factory()->state(['nama' => 'Kategori Draf']))->create();
        Kategori::factory()->create(['nama' => 'Kategori Kosong']);

        $this->get('/edukasi')->assertSee('Kategori Berisi')->assertDontSee('Kategori Draf')->assertDontSee('Kategori Kosong');
    }

    public function test_pencarian_mencocokkan_judul_dan_isi(): void
    {
        Materi::factory()->terbit()->create(['judul' => 'Kompos Dapur']);
        Materi::factory()->terbit()->create(['judul' => 'Lainnya', 'penjelasan' => 'Tentang kompos cair.']);
        Materi::factory()->terbit()->create(['judul' => 'Jalur Evakuasi']);

        $this->get('/edukasi?cari=kompos')->assertOk()->assertSee('Kompos Dapur')->assertSee('Lainnya')->assertDontSee('Jalur Evakuasi');
    }

    public function test_pencarian_juga_mencocokkan_nama_kategori(): void
    {
        Materi::factory()->terbit()->for(Kategori::factory()->state(['nama' => 'Jamur Tiram']))->create(['judul' => 'Panen Pagi']);
        Materi::factory()->terbit()->create(['judul' => 'Jalur Evakuasi']);

        $this->get('/edukasi?cari=tiram')->assertOk()->assertSee('Panen Pagi')->assertDontSee('Jalur Evakuasi');
        $this->get('/edukasi?cari=jam')->assertSee('Panen Pagi');
        // Potongan di tengah kata tidak ikut: "ura" ada di "Jamur" tetapi bukan awal kata.
        $this->get('/edukasi?cari=ura')->assertDontSee('Panen Pagi');
    }

    public function test_saring_kategori_dan_hasil_kosong_yang_ramah(): void
    {
        $fermentasi = Kategori::factory()->create(['nama' => 'Fermentasi', 'slug' => 'fermentasi']);
        Materi::factory()->terbit()->for($fermentasi)->create(['judul' => 'Tape Singkong']);
        Materi::factory()->terbit()->create(['judul' => 'Jalur Evakuasi']);

        $this->get('/edukasi?kategori=fermentasi')->assertSee('Tape Singkong')->assertDontSee('Jalur Evakuasi');
        $this->get('/edukasi?kategori=tidak-ada')->assertOk()->assertSee('Tidak ada materi yang cocok');
        $this->get('/edukasi?kategori=fermentasi&cari=evakuasi')->assertOk()->assertSee('Tidak ada materi yang cocok');
    }

    public function test_kartu_materi_punya_tombol_mulai_belajar_tanpa_lanjutkan(): void
    {
        Materi::factory()->terbit()->create();

        $this->get('/edukasi')->assertOk()->assertSee('Mulai Belajar')->assertDontSee('Lanjutkan Belajar');
    }

    public function test_tips_sirkularitas_tampil_per_kategori_dan_mengikuti_saringan(): void
    {
        $a = Kategori::factory()->create(['slug' => 'kat-a', 'tips_sirkular' => 'Tips untuk A']);
        $b = Kategori::factory()->create(['slug' => 'kat-b', 'tips_sirkular' => 'Tips untuk B']);
        Materi::factory()->terbit()->for($a)->create();
        Materi::factory()->terbit()->for($b)->create();

        // Tampilan "Semua" tidak menampilkan tips; tips hanya muncul di kategori masing-masing.
        $this->get('/edukasi')->assertOk()->assertDontSee('Tips Sirkularitas');
        $this->get('/edukasi?kategori=kat-a')->assertSee('Tips untuk A')->assertDontSee('Tips untuk B');
        // Kotak tips berada di akhir daftar, setelah kartu materi.
        $this->get('/edukasi?kategori=kat-b')->assertSeeInOrder(['Mulai Belajar', 'Tips Sirkularitas', 'Tips untuk B']);
    }

    public function test_tanpa_tips_kotak_tips_tidak_tampil_dan_tips_aman_dari_skrip(): void
    {
        $kosong = Kategori::factory()->create();
        Materi::factory()->terbit()->for($kosong)->create();
        $this->get("/edukasi?kategori={$kosong->slug}")->assertDontSee('Tips Sirkularitas');

        $kosong->update(['tips_sirkular' => '<script>x</script>']);
        $this->get("/edukasi?kategori={$kosong->slug}")->assertDontSee('<script>x</script>', false)->assertSee('&lt;script&gt;x&lt;/script&gt;', false);
    }

    public function test_kartu_memakai_sampul_video_bila_tanpa_gambar(): void
    {
        Materi::factory()->terbit()->create(['video_youtube' => 'dQw4w9WgXcQ']);

        $this->get('/edukasi')->assertSee('https://i.ytimg.com/vi/dQw4w9WgXcQ/mqdefault.jpg', false);
    }

    public function test_detail_menampilkan_bagian_dan_tautan_ke_bagian_lain(): void
    {
        $kategori = Kategori::factory()->create(['nama' => 'Jamur Uji']);
        $a = Materi::factory()->terbit()->for($kategori)->create(['judul' => 'Bagian Satu', 'urutan' => 1]);
        $b = Materi::factory()->terbit()->for($kategori)->create(['judul' => 'Bagian Dua', 'urutan' => 2]);
        $c = Materi::factory()->terbit()->for($kategori)->create(['judul' => 'Bagian Tiga', 'urutan' => 3]);

        $this->get("/edukasi/{$b->slug}")->assertOk()
            ->assertSee('Jamur Uji · Bagian 2 dari 3')
            ->assertSee('2/3')
            ->assertSee(route('edukasi.show', $a->slug), false)
            ->assertSee(route('edukasi.show', $c->slug), false)
            ->assertSeeInOrder(['Bagian Satu', 'Bagian Dua', 'Bagian Tiga']);

        $this->get("/edukasi/{$a->slug}")->assertSee('Ini bagian pertama');
        $this->get("/edukasi/{$c->slug}")->assertSee('Bagian terakhir');
    }

    public function test_daftar_kategori_berurutan_menurut_bagian_dan_bernomor(): void
    {
        $kategori = Kategori::factory()->create(['slug' => 'urut']);
        Materi::factory()->terbit()->for($kategori)->create(['judul' => 'Kedua Dibuat Pertama', 'urutan' => 2]);
        Materi::factory()->terbit()->for($kategori)->create(['judul' => 'Pertama Dibuat Kedua', 'urutan' => 1]);

        $this->get('/edukasi?kategori=urut')->assertOk()
            ->assertSeeInOrder(['Pertama Dibuat Kedua', 'Kedua Dibuat Pertama'])
            ->assertSee('Bagian 1')
            ->assertSee('Bagian 2');
        // Saat mencari, nomor bagian disembunyikan karena posisinya tidak lagi sama dengan urutan belajar.
        $this->get('/edukasi?kategori=urut&cari=Dibuat')->assertDontSee('Bagian 1');
        $this->get('/edukasi')->assertDontSee('Bagian 1');
    }

    public function test_pencarian_tidak_valid_tidak_membuat_galat_server(): void
    {
        $this->get('/edukasi?cari[]=x')->assertRedirect();
        $this->get('/edukasi?cari='.str_repeat('a', 101))->assertRedirect();
    }

    public function test_paginasi_mempertahankan_saringan(): void
    {
        Materi::factory()->count(13)->terbit()->create(['ringkasan' => 'kompos']);

        $this->get('/edukasi?cari=kompos')->assertOk()->assertSee('cari=kompos&amp;page=2', false);
    }

    public function test_detail_menampilkan_penjelasan_dan_langkah_berurutan(): void
    {
        $materi = Materi::factory()->terbit()->create([
            'penjelasan' => 'Isi penjelasan',
        ]);
        Langkah::factory()->for($materi)->create(['urutan' => 2, 'judul' => 'Langkah Kedua']);
        Langkah::factory()->for($materi)->create(['urutan' => 1, 'judul' => 'Langkah Pertama']);

        $this->get("/edukasi/{$materi->slug}")->assertOk()
            ->assertSee('Isi penjelasan')
            ->assertSeeInOrder(['1. Langkah Pertama', '2. Langkah Kedua']);
    }

    public function test_isi_materi_ditampilkan_aman_dari_skrip(): void
    {
        $materi = Materi::factory()->terbit()->create(['penjelasan' => '<script>alert(1)</script>']);

        $this->get("/edukasi/{$materi->slug}")->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_menu_muncul_saat_bendera_modul_menyala(): void
    {
        config(['modul.edukasi.aktif' => true]);
        $this->assertTrue(Modul::semua()['edukasi']['tersedia']);

        config(['modul.edukasi.aktif' => false]);
        $this->assertFalse(Modul::semua()['edukasi']['tersedia']);
    }

    public function test_video_youtube_tampil_sebagai_tombol_putar_dengan_kode_yang_aman(): void
    {
        $materi = Materi::factory()->terbit()->create(['video_youtube' => 'dQw4w9WgXcQ']);

        $this->get('/edukasi')->assertSee('Ada video');
        $this->get("/edukasi/{$materi->slug}")->assertOk()
            ->assertSee('data-youtube="dQw4w9WgXcQ"', false)
            ->assertSee('Putar video');
    }

    public function test_materi_tanpa_video_tidak_menampilkan_pemutar(): void
    {
        $materi = Materi::factory()->terbit()->create();

        $this->get("/edukasi/{$materi->slug}")->assertOk()->assertDontSee('Putar video');
        $this->get('/edukasi')->assertDontSee('Ada video');
    }
}
