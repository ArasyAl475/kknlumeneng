<?php

namespace Tests\Feature\Edukasi;

use App\Models\Edukasi\Kategori;
use App\Models\Edukasi\Materi;
use Database\Seeders\EdukasiContohSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContohSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_membuat_materi_terbit_dengan_langkah_dan_kode_video_yang_sah(): void
    {
        $this->seed(EdukasiContohSeeder::class);

        $this->assertGreaterThan(0, Materi::query()->count());
        $this->assertSame(0, Materi::query()->whereNull('diterbitkan_pada')->count());
        $this->assertSame(0, Materi::query()->doesntHave('langkah')->count());
        $this->assertGreaterThan(0, Materi::query()->whereNotNull('video_youtube')->count());

        foreach (Materi::query()->whereNotNull('video_youtube')->pluck('video_youtube') as $kode) {
            $this->assertMatchesRegularExpression('~^[A-Za-z0-9_-]{11}$~', $kode);
        }
    }

    public function test_setiap_kategori_awal_punya_materi_dan_tampil_ke_warga(): void
    {
        $this->seed(EdukasiContohSeeder::class);

        $this->assertSame(0, Kategori::query()->doesntHave('materi')->count());
        $this->get('/edukasi')->assertOk()->assertSee('Mulai Belajar')->assertSee('Cara Merawat Jamur Tiram Sampai Panen');
    }

    public function test_materi_tiap_kategori_dinomori_bagian_berurutan_tanpa_loncat(): void
    {
        $this->seed(EdukasiContohSeeder::class);

        foreach (Kategori::query()->with('materi')->get() as $kategori) {
            $this->assertSame(range(1, $kategori->materi->count()), $kategori->materi->sortBy('urutan')->pluck('urutan')->values()->all());
        }
    }

    public function test_aman_diulang_tanpa_menggandakan_materi_atau_langkah(): void
    {
        $this->seed(EdukasiContohSeeder::class);
        $materi = Materi::query()->count();
        $langkah = Materi::query()->withCount('langkah')->get()->sum('langkah_count');

        $this->seed(EdukasiContohSeeder::class);

        $this->assertSame($materi, Materi::query()->count());
        $this->assertEquals($langkah, Materi::query()->withCount('langkah')->get()->sum('langkah_count'));
    }

    public function test_menolak_berjalan_di_produksi(): void
    {
        $this->app['env'] = 'production';

        // Dipanggil langsung: perintah db:seed di produksi sudah meminta konfirmasi sendiri.
        $this->app->make(EdukasiContohSeeder::class)->run();

        $this->assertSame(0, Materi::query()->count());
    }
}
