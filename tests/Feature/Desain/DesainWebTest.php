<?php

namespace Tests\Feature\Desain;

use App\Models\Desain\SlotGambar;
use App\Models\Profil\Pengaturan;
use App\Models\User;
use App\Services\Desain\DaftarSlot;
use App\Services\Desain\GambarSitus;
use Database\Seeders\DesainSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DesainWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function masukAdmin(): void
    {
        $this->actingAs(User::factory()->admin()->create());
    }

    private function slot(string $kunci): SlotGambar
    {
        return SlotGambar::query()->where('kunci', $kunci)->firstOrFail();
    }

    public function test_hanya_admin_yang_bisa_membuka(): void
    {
        $this->seed(DesainSeeder::class);
        $slot = $this->slot('beranda.hero');

        $this->get(route('admin.desain.index'))->assertRedirect(route('login'));
        $this->put(route('admin.desain.update', $slot), ['alt' => 'x'])->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create());
        $this->get(route('admin.desain.index'))->assertForbidden();
        $this->post(route('admin.desain.pulihkan', $slot))->assertForbidden();
    }

    public function test_seeder_mengisi_semua_slot_dengan_gambar_bawaan_yang_ada(): void
    {
        $this->seed(DesainSeeder::class);

        $this->assertSame(count(DaftarSlot::SEMUA), SlotGambar::query()->count());

        foreach (DaftarSlot::SEMUA as $kunci => $isi) {
            $slot = $this->slot($kunci);
            $this->assertNull($slot->path, $kunci);
            $this->assertFileExists(public_path($slot->gambar_bawaan), $kunci);
        }

        $this->assertFileExists(public_path(DaftarSlot::CADANGAN));
    }

    public function test_seeder_tidak_menimpa_pilihan_admin(): void
    {
        $this->seed(DesainSeeder::class);
        $this->slot('beranda.hero')->update(['path' => 'desain/punya-admin.webp', 'alt' => 'Sawah pagi']);

        $this->seed(DesainSeeder::class);

        $this->assertSame('desain/punya-admin.webp', $this->slot('beranda.hero')->path);
        $this->assertSame('Sawah pagi', $this->slot('beranda.hero')->alt);
    }

    public function test_seeder_memindahkan_foto_profil_lama(): void
    {
        Pengaturan::simpan(['foto_sampul' => 'profil/sampul-lama.webp']);

        $this->seed(DesainSeeder::class);

        $this->assertSame('profil/sampul-lama.webp', $this->slot('profil.hero')->path);
        $this->assertNull($this->slot('profil.kepala-desa')->path);
    }

    public function test_halaman_menampilkan_slot_per_halaman(): void
    {
        $this->masukAdmin();

        // Tanpa seeder pun daftar slot lengkap, karena halaman menyinkronkan dari kode.
        $this->get(route('admin.desain.index'))
            ->assertOk()
            ->assertSee('Desain Web')
            ->assertSeeInOrder(['Halaman Beranda', 'Foto utama Beranda', 'Halaman Profil Desa', 'Foto kepala desa', 'Halaman Masuk Admin'])
            ->assertSee('Muncul di:')
            ->assertSee('1920 × 1080 px')
            ->assertSee('Gambar bawaan')
            ->assertSee(asset('images/lumeneng/beranda-hero.webp'), false)
            ->assertDontSee('Pulihkan bawaan');
    }

    public function test_helper_memakai_bawaan_bila_slot_kosong_atau_berkas_hilang(): void
    {
        $this->assertSame(asset('images/lumeneng/beranda-hero.webp'), site_image('beranda.hero'));
        $this->assertSame(asset(DaftarSlot::CADANGAN), site_image('tidak.ada'));

        $this->seed(DesainSeeder::class);
        $this->slot('beranda.hero')->update(['path' => 'desain/sudah-dihapus.webp']);
        app(GambarSitus::class)->lupakan();

        $this->assertSame(asset('images/lumeneng/beranda-hero.webp'), site_image('beranda.hero'));
        $this->assertSame('', site_image_alt('beranda.hero'));
        $this->assertSame('Foto kepala desa', site_image_alt('profil.kepala-desa'));
    }

    public function test_unggah_gambar_dikompres_bernama_acak_dan_tampil_di_situs(): void
    {
        $this->masukAdmin();
        $this->seed(DesainSeeder::class);
        $slot = $this->slot('beranda.hero');
        $bawaan = public_path($slot->gambar_bawaan);
        $isiBawaan = file_get_contents($bawaan);

        $this->put(route('admin.desain.update', $slot), [
            'gambar' => UploadedFile::fake()->image('sawah.jpg', 4000, 2250),
            'alt' => '  Kebun kopi di lereng  ',
        ])->assertRedirect(route('admin.desain.index').'#slot-'.$slot->id)->assertSessionHas('sukses');

        $slot->refresh();
        $this->assertStringStartsWith('desain/', $slot->path);
        $this->assertDoesNotMatchRegularExpression('/sawah/', $slot->path);
        $this->assertSame('Kebun kopi di lereng', $slot->alt);
        Storage::disk('public')->assertExists($slot->path);

        // Diperkecil: sisi terpanjang tidak lebih dari 2000 piksel.
        [$lebar] = getimagesizefromstring(Storage::disk('public')->get($slot->path));
        $this->assertLessThanOrEqual(2000, $lebar);

        // Berkas bawaan tidak pernah disentuh.
        $this->assertSame($isiBawaan, file_get_contents($bawaan));

        $this->get('/')->assertSee(asset('storage/'.$slot->path), false)->assertSee('alt="Kebun kopi di lereng"', false);
    }

    public function test_unggah_gambar_transparan_tetap_transparan_untuk_peta_3d(): void
    {
        $this->masukAdmin();
        $this->seed(DesainSeeder::class);
        $slot = $this->slot('beranda.peta-3d');

        $png = imagecreatetruecolor(400, 250);
        imagealphablending($png, false);
        imagesavealpha($png, true);
        imagefill($png, 0, 0, (int) imagecolorallocatealpha($png, 0, 0, 0, 127));
        imagefilledrectangle($png, 100, 60, 300, 190, (int) imagecolorallocatealpha($png, 30, 120, 60, 0));
        $berkas = tempnam(sys_get_temp_dir(), 'peta').'.png';
        imagepng($png, $berkas);

        $this->put(route('admin.desain.update', $slot), ['gambar' => new UploadedFile($berkas, 'peta.png', 'image/png', null, true)])
            ->assertSessionHasNoErrors();

        $hasil = imagecreatefromstring(Storage::disk('public')->get($slot->fresh()->path));
        // Sudut kiri atas harus tetap bening (alfa GD 127), bukan berubah putih atau hitam; isi tetap buram.
        $this->assertSame(127, (imagecolorat($hasil, 2, 2) >> 24) & 0x7F);
        $this->assertSame(0, (imagecolorat($hasil, 200, 125) >> 24) & 0x7F);
    }

    public function test_ganti_gambar_menghapus_unggahan_lama(): void
    {
        $this->masukAdmin();
        $this->seed(DesainSeeder::class);
        $slot = $this->slot('berita.hero');

        $this->put(route('admin.desain.update', $slot), ['gambar' => UploadedFile::fake()->image('a.png', 800, 300)]);
        $pertama = $slot->refresh()->path;

        $this->put(route('admin.desain.update', $slot), ['gambar' => UploadedFile::fake()->image('b.webp', 800, 300)]);
        $kedua = $slot->refresh()->path;

        $this->assertNotSame($pertama, $kedua);
        Storage::disk('public')->assertMissing($pertama);
        Storage::disk('public')->assertExists($kedua);
    }

    public function test_hanya_teks_alt_yang_diubah_tanpa_gambar(): void
    {
        $this->masukAdmin();
        $this->seed(DesainSeeder::class);
        $slot = $this->slot('beranda.produk');

        $this->put(route('admin.desain.update', $slot), ['alt' => 'Kopi bubuk warga'])->assertSessionHasNoErrors();

        $this->assertSame('Kopi bubuk warga', $slot->refresh()->alt);
        $this->assertNull($slot->path);
    }

    public function test_format_dan_ukuran_divalidasi(): void
    {
        $this->masukAdmin();
        $this->seed(DesainSeeder::class);
        $slot = $this->slot('beranda.hero');
        $kantong = 'slot'.$slot->id;

        $this->put(route('admin.desain.update', $slot), ['gambar' => UploadedFile::fake()->create('virus.php', 10, 'text/plain')])
            ->assertSessionHasErrorsIn($kantong, 'gambar');
        $this->put(route('admin.desain.update', $slot), ['gambar' => UploadedFile::fake()->create('dok.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrorsIn($kantong, 'gambar');
        $this->put(route('admin.desain.update', $slot), ['gambar' => UploadedFile::fake()->image('besar.jpg')->size(3073)])
            ->assertSessionHasErrorsIn($kantong, 'gambar')
            ->assertRedirect(route('admin.desain.index').'#slot-'.$slot->id);
        $this->put(route('admin.desain.update', $slot), ['alt' => str_repeat('a', 201)])
            ->assertSessionHasErrorsIn($kantong, 'alt');

        $this->assertNull($slot->refresh()->path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_pulihkan_bawaan_menghapus_unggahan(): void
    {
        $this->masukAdmin();
        $this->seed(DesainSeeder::class);
        $slot = $this->slot('profil.kepala-desa');

        $this->put(route('admin.desain.update', $slot), ['gambar' => UploadedFile::fake()->image('kades.jpg', 800, 1000), 'alt' => 'Foto Bapak Kades']);
        $jalur = $slot->refresh()->path;
        $this->get(route('admin.desain.index'))->assertSee('Pulihkan bawaan')->assertSee('Gambar sendiri');

        $this->post(route('admin.desain.pulihkan', $slot))->assertSessionHas('sukses');

        $slot->refresh();
        $this->assertNull($slot->path);
        $this->assertSame('Foto kepala desa', $slot->alt);
        Storage::disk('public')->assertMissing($jalur);
        $this->assertSame(asset('images/default/profil-kepala-desa.svg'), site_image('profil.kepala-desa'));
    }

    public function test_pulihkan_tidak_menghapus_foto_di_luar_folder_desain(): void
    {
        $this->masukAdmin();
        $this->seed(DesainSeeder::class);
        Storage::disk('public')->put('profil/sampul-lama.webp', 'isi');
        $slot = $this->slot('profil.hero');
        $slot->update(['path' => 'profil/sampul-lama.webp']);

        $this->post(route('admin.desain.pulihkan', $slot));

        Storage::disk('public')->assertExists('profil/sampul-lama.webp');
    }

    public function test_menu_desain_web_ada_di_sidebar(): void
    {
        $this->masukAdmin();

        $this->get(route('admin.beranda'))->assertSee('Desain Web')->assertSee(route('admin.desain.index'), false);
    }
}
