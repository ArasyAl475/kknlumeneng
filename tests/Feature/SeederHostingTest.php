<?php

namespace Tests\Feature;

use App\Models\Desain\SlotGambar;
use App\Models\Edukasi\Kategori;
use App\Models\Edukasi\Materi;
use App\Models\Ews\Pengaturan as PengaturanEws;
use App\Models\Ews\Titik;
use App\Models\Ews\Wilayah;
use App\Models\Profil\Berita;
use App\Models\Profil\Foto;
use App\Models\Profil\Lembaga;
use App\Models\Profil\Pengaturan as PengaturanProfil;
use App\Models\Profil\Perangkat;
use App\Models\Profil\Potensi;
use App\Models\Toko\Marketplace;
use App\Models\Toko\Produk;
use App\Models\User;
use App\Services\Desain\DaftarSlot;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProfilContohSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Seeder untuk hosting: `php artisan migrate --force --seed` di server harus mengisi semua data awal yang
 * benar (akun admin, ambang EWS, wilayah, kategori, slot gambar, potensi) tanpa data karangan, dan aman diulang.
 */
class SeederHostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['admin.email' => 'admin@lumeneng.example', 'admin.password' => 'Kunci-Rahasia-2026x']);
    }

    /** @return array<string, int> */
    private function hitung(): array
    {
        return [
            'user' => User::query()->count(),
            'ews_ambang' => PengaturanEws::query()->count(),
            'wilayah' => Wilayah::query()->count(),
            'titik' => Titik::query()->count(),
            'marketplace' => Marketplace::query()->count(),
            'produk' => Produk::query()->count(),
            'kategori' => Kategori::query()->count(),
            'slot' => SlotGambar::query()->count(),
            'potensi' => Potensi::query()->count(),
            'foto' => Foto::query()->count(),
            'perangkat' => Perangkat::query()->count(),
            'lembaga' => Lembaga::query()->count(),
            'berita' => Berita::query()->count(),
        ];
    }

    public function test_seeder_server_mengisi_data_awal_tanpa_data_karangan_dan_aman_diulang(): void
    {
        config(['seeder.contoh' => false]);

        $this->seed(DatabaseSeeder::class);
        $sekali = $this->hitung();

        // Data awal yang dibutuhkan situs agar langsung berfungsi.
        $this->assertTrue(User::query()->where('email', 'admin@lumeneng.example')->value('is_admin'));
        $this->assertGreaterThan(0, $sekali['ews_ambang']);
        $this->assertSame(1, $sekali['wilayah']);
        $this->assertSame(1, $sekali['titik']);
        $this->assertSame(1, Wilayah::query()->where('nama', 'Titik Area 2')->value('id_alat'));
        $this->assertSame(5, $sekali['produk']);
        $this->assertGreaterThan(0, $sekali['marketplace']);
        $this->assertSame(3, $sekali['kategori']);
        $this->assertSame(0, Materi::query()->count());
        $this->assertSame(count(DaftarSlot::SEMUA), $sekali['slot']);
        $this->assertSame(5, $sekali['potensi']);
        $this->assertSame(20, $sekali['foto']);
        $this->assertSame('884.6', PengaturanProfil::semua()['luas_wilayah']);

        // Tidak ada data karangan: nama perangkat, lembaga, berita, foto contoh, dan kontak palsu.
        $this->assertSame(0, $sekali['perangkat']);
        $this->assertSame(0, $sekali['lembaga']);
        $this->assertSame(0, $sekali['berita']);
        $this->assertSame(0, Foto::query()->whereNull('potensi_id')->count());
        $profil = PengaturanProfil::semua();
        foreach (['nama_kepala_desa', 'sambutan', 'sejarah', 'visi', 'misi', 'telepon', 'email', 'alamat_kantor'] as $kunci) {
            $this->assertTrue(blank($profil[$kunci] ?? null), "{$kunci} harus kosong di server, diisi admin");
        }

        // Dijalankan lagi: tidak ada duplikat.
        $this->seed(DatabaseSeeder::class);
        $this->assertSame($sekali, $this->hitung());
    }

    public function test_halaman_publik_tetap_terbuka_dengan_data_awal_server(): void
    {
        config(['seeder.contoh' => false]);
        $this->seed(DatabaseSeeder::class);

        foreach (['/', '/profil', '/ews', '/galeri', '/berita', '/edukasi', '/toko'] as $alamat) {
            $this->get($alamat)->assertOk();
        }
        $this->get('/profil')->assertSee('Potensi &amp; penghasil desa', false)->assertDontSee('(contoh)');
    }

    public function test_data_contoh_ikut_terisi_bila_diaktifkan(): void
    {
        config(['seeder.contoh' => true]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(11, Perangkat::query()->count());
        $this->assertSame(6, Berita::query()->count());
    }

    public function test_seeder_contoh_menolak_jalan_bila_dinonaktifkan(): void
    {
        config(['seeder.contoh' => false]);

        $this->seed(ProfilContohSeeder::class);

        $this->assertSame(0, Perangkat::query()->count());
        $this->assertSame(0, Berita::query()->count());
    }
}
