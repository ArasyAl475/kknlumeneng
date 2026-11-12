<?php

namespace Tests\Feature\Profil;

use App\Models\Profil\Berita;
use App\Models\Profil\Foto;
use App\Models\Profil\Lembaga;
use App\Models\Profil\Pengaturan;
use App\Models\Profil\Perangkat;
use Database\Seeders\ProfilSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HalamanProfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_profil_kosong_tetap_tampil_dengan_pesan_ramah(): void
    {
        $this->get(route('profil.index'))->assertOk()
            ->assertSee('Desa Lumeneng')
            ->assertSee('Kec. Paninggaran, Kab. Pekalongan, Jawa Tengah')
            ->assertSee('Profil desa sedang disiapkan.')
            ->assertDontSee('Desa dalam angka');
    }

    public function test_profil_menampilkan_isian_admin(): void
    {
        Pengaturan::simpan([
            'tagline' => 'Desa sejuk di lereng bukit',
            'nama_kepala_desa' => 'Bapak Contoh',
            'sambutan' => "Paragraf satu.\n\nParagraf dua.",
            'sejarah' => 'Asal-usul nama desa.',
            'jumlah_penduduk' => '3412',
            'luas_wilayah' => '642.5',
            'batas_utara' => 'Desa Utara',
            'telepon' => '0812-3456',
            'jam_pelayanan' => "Senin: 08.00\nJumat: 08.00",
            'tautan_peta' => 'https://maps.app.goo.gl/contoh',
        ]);

        $this->get(route('profil.index'))->assertOk()
            ->assertSee('Desa sejuk di lereng bukit')
            ->assertSee('Bapak Contoh')
            ->assertSeeInOrder(['Paragraf satu.', 'Paragraf dua.'])
            ->assertSee('Asal-usul nama desa.')
            // Angka memakai titik ribuan dan koma desimal.
            ->assertSee('3.412')
            ->assertSee('642,5')
            ->assertSee('Desa Utara')
            ->assertSee('href="tel:08123456"', false)
            ->assertSee('https://maps.app.goo.gl/contoh', false)
            ->assertDontSee('Profil desa sedang disiapkan.');
    }

    public function test_tautan_peta_berbahaya_tidak_menjadi_tautan(): void
    {
        Pengaturan::simpan(['tautan_peta' => 'javascript:alert(1)', 'alamat_kantor' => 'Jl. Desa']);

        $this->get(route('profil.index'))->assertOk()->assertDontSee('javascript:alert(1)', false);
    }

    public function test_isian_admin_ditampilkan_aman_tanpa_html(): void
    {
        Pengaturan::simpan(['sejarah' => '<script>alert("x")</script>']);

        // Data lama di basis data pun tetap dibersihkan saat ditampilkan, bukan hanya saat disimpan.
        $this->get(route('profil.index'))->assertOk()
            ->assertDontSee('alert("x")', false);
    }

    public function test_visi_misi_bernomor_otomatis(): void
    {
        Pengaturan::simpan(['visi' => 'Desa aman dan sejahtera.', 'misi' => "1. Pelayanan cepat\n- Siaga bencana\n\nEkonomi warga"]);

        $this->get(route('profil.index'))->assertOk()
            ->assertSee('Desa aman dan sejahtera.')
            ->assertSeeInOrder(['Misi 1: ', 'Pelayanan cepat', 'Misi 2: ', 'Siaga bencana', 'Misi 3: ', 'Ekonomi warga'], false)
            ->assertDontSee('1. Pelayanan cepat');
    }

    public function test_visi_misi_kosong(): void
    {
        $this->get(route('profil.index'))->assertOk()->assertSee('Visi dan misi belum diisi.');
    }

    public function test_pemerintahan_menyusun_bagan_dari_atas(): void
    {
        Perangkat::factory()->create(['nama' => 'Kaur Dua', 'jabatan' => 'Kaur Umum', 'kelompok' => 'kaur', 'urutan' => 2]);
        Perangkat::factory()->create(['nama' => 'Kaur Satu', 'jabatan' => 'Kaur Keuangan', 'kelompok' => 'kaur', 'urutan' => 1]);
        Perangkat::factory()->create(['nama' => 'Kades Contoh', 'jabatan' => 'Kepala Desa', 'kelompok' => 'kepala']);
        Perangkat::factory()->create(['nama' => 'Kadus Contoh', 'jabatan' => 'Kepala Dusun Krajan', 'kelompok' => 'kadus']);
        Lembaga::query()->create(['nama' => 'Karang Taruna Giri', 'ketua' => 'Mas Ketua']);

        $this->get(route('profil.index'))->assertOk()
            ->assertSeeInOrder(['Kades Contoh', 'Sekretariat', 'Kaur Satu', 'Kaur Dua', 'Pelaksana Kewilayahan', 'Kadus Contoh'])
            // Kelompok tanpa anggota tidak ditampilkan.
            ->assertDontSee('Pelaksana Teknis')
            ->assertSee('Karang Taruna Giri')
            ->assertSee('Mas Ketua');
    }

    public function test_pemerintahan_kosong(): void
    {
        $this->get(route('profil.index'))->assertOk()->assertSee('Struktur pemerintahan belum diisi.');
    }

    public function test_profil_tidak_lagi_menampilkan_kabar_terbaru(): void
    {
        Berita::factory()->create(['judul' => 'Kabar baru', 'diterbitkan_pada' => now()->subDay()]);

        $this->get(route('profil.index'))->assertOk()->assertDontSee('Kabar baru')->assertDontSee('Kabar terbaru dari desa');
    }

    public function test_tiga_bagian_profil_ada_di_satu_halaman(): void
    {
        $this->get(route('profil.index'))->assertOk()
            ->assertSeeInOrder(['href="#tentang"', 'href="#visi-misi"', 'href="#pemerintahan"'], false)
            ->assertSeeInOrder(['id="tentang"', 'id="visi-misi"', 'id="pemerintahan"'], false);
    }

    public function test_alamat_lama_diarahkan_ke_bagiannya(): void
    {
        $this->get(route('profil.visi-misi'))->assertStatus(301)->assertRedirect(route('profil.index').'#visi-misi');
        $this->get(route('profil.pemerintahan'))->assertStatus(301)->assertRedirect(route('profil.index').'#pemerintahan');
    }

    public function test_seeder_mengisi_semua_halaman_dan_aman_diulang(): void
    {
        Storage::fake('public');

        $this->seed(ProfilSeeder::class);
        Pengaturan::simpan(['visi' => 'Visi tulisan admin']);
        $this->seed(ProfilSeeder::class);

        $this->assertSame(11, Perangkat::query()->count());
        $this->assertSame(6, Lembaga::query()->count());
        $this->assertSame(6, Berita::query()->count());
        // 4 foto contoh + 20 foto potensi (5 + 4 + 3 + 5 + 3) yang otomatis ikut masuk galeri.
        $this->assertSame(24, Foto::query()->count());
        $this->assertSame(20, Foto::query()->whereNotNull('potensi_id')->count());
        $this->assertSame(21, Foto::query()->whereNull('berita_id')->count());
        Foto::query()->pluck('gambar')->each(fn (string $jalur) => Storage::disk('public')->assertExists($jalur));
        // Foto contoh dibuat terakhir (paling baru), jadi tampil di halaman pertama galeri; foto potensi paling lama ada di halaman berikutnya.
        $this->get(route('galeri.index'))->assertOk()->assertSee('Kopi hasil kebun warga (contoh)')->assertSee('Mata air desa');
        $this->get(route('galeri.index', ['page' => 2]))->assertOk()->assertSee('Hutan lindung');

        $this->get(route('profil.index'))->assertOk()->assertSee('Sambutan')->assertSee('Desa dalam angka')->assertSee('Batas wilayah');
        // Seeder ulang tidak menimpa tulisan admin.
        $this->get(route('profil.index'))->assertOk()->assertSee('Visi tulisan admin');
        $this->get(route('profil.index'))->assertOk()->assertSee('Kepala Dusun Krajan');
    }
}
