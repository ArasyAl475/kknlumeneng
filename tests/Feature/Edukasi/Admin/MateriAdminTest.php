<?php

namespace Tests\Feature\Edukasi\Admin;

use App\Models\Edukasi\Kategori;
use App\Models\Edukasi\Langkah;
use App\Models\Edukasi\Materi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

class MateriAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
        Storage::fake('public');
    }

    /**
     * @return array<string, mixed>
     */
    private function isian(array $tambahan = []): array
    {
        return $tambahan + [
            'kategori_id' => Kategori::factory()->create()->id,
            'judul' => 'Cara Membuat Tape',
            'ringkasan' => 'Panduan singkat.',
            'penjelasan' => 'Tape dibuat dari singkong.',
        ];
    }

    public function test_daftar_kosong_dan_formulir_tampil(): void
    {
        $this->get(route('admin.edukasi.materi.index'))->assertOk()->assertSee('Belum ada materi');
        $this->get(route('admin.edukasi.materi.create'))->assertOk()->assertSee('Tambahkan kategori dulu');

        Kategori::factory()->create(['nama' => 'Fermentasi']);
        $this->get(route('admin.edukasi.materi.create'))->assertOk()->assertSee('Fermentasi');
    }

    public function test_daftar_menampilkan_status_kata_dan_jumlah_langkah(): void
    {
        Langkah::factory()->count(2)->for(Materi::factory()->create(['judul' => 'Masih Draf']))->create();
        Materi::factory()->terbit()->create(['judul' => 'Sudah Tayang']);

        $this->get(route('admin.edukasi.materi.index'))->assertOk()
            ->assertSee('Masih Draf')->assertSee('Draf')
            ->assertSee('Sudah Tayang')->assertSee('Terbit')
            ->assertSee('2 langkah');
    }

    public function test_materi_baru_tersimpan_sebagai_draf_dan_mendapat_alamat(): void
    {
        $this->post(route('admin.edukasi.materi.store'), $this->isian())->assertSessionHasNoErrors();

        $materi = Materi::query()->sole();
        $this->assertFalse($materi->sudahTerbit());
        $this->assertSame('cara-membuat-tape', $materi->slug);
        $this->get(route('admin.edukasi.materi.edit', $materi))->assertOk()->assertSee('Belum ada langkah');
    }

    public function test_judul_kembar_mendapat_alamat_berbeda_dan_judul_simbol_tetap_punya_alamat(): void
    {
        $this->post(route('admin.edukasi.materi.store'), $this->isian());
        $this->post(route('admin.edukasi.materi.store'), $this->isian());
        $this->post(route('admin.edukasi.materi.store'), $this->isian(['judul' => '!!!']));

        $this->assertSame(
            ['cara-membuat-tape', 'cara-membuat-tape-2', 'materi'],
            Materi::query()->orderBy('id')->pluck('slug')->all(),
        );
    }

    public function test_isian_wajib_divalidasi(): void
    {
        $this->post(route('admin.edukasi.materi.store'), [])->assertSessionHasErrors(['kategori_id', 'judul', 'ringkasan']);
        $this->post(route('admin.edukasi.materi.store'), $this->isian(['kategori_id' => 99999]))->assertSessionHasErrors('kategori_id');
    }

    public function test_mengubah_judul_tidak_mengubah_alamat_dan_penjelasan_bisa_dikosongkan(): void
    {
        $materi = Materi::factory()->create(['slug' => 'alamat-lama', 'penjelasan' => 'Lama']);

        $this->put(route('admin.edukasi.materi.update', $materi), [
            'kategori_id' => $materi->kategori_id,
            'judul' => 'Judul Baru',
            'ringkasan' => 'Ringkasan baru',
            'penjelasan' => '',
        ])->assertSessionHasNoErrors();

        $materi->refresh();
        $this->assertSame('Judul Baru', $materi->judul);
        $this->assertSame('alamat-lama', $materi->slug);
        $this->assertNull($materi->penjelasan);
    }

    public function test_materi_kosong_tidak_bisa_diterbitkan(): void
    {
        $materi = Materi::factory()->create(['penjelasan' => null]);

        $this->post(route('admin.edukasi.materi.terbitkan', $materi))->assertSessionHas('galat');

        $this->assertFalse($materi->refresh()->sudahTerbit());
    }

    public function test_materi_hanya_berisi_langkah_bisa_diterbitkan(): void
    {
        $materi = Materi::factory()->create(['penjelasan' => null]);
        Langkah::factory()->for($materi)->create();

        $this->post(route('admin.edukasi.materi.terbitkan', $materi))->assertSessionHas('sukses');

        $this->assertTrue($materi->refresh()->sudahTerbit());
    }

    public function test_terbit_dan_tarik_mengatur_yang_terlihat_warga(): void
    {
        $materi = Materi::factory()->create();

        $this->get("/edukasi/{$materi->slug}")->assertNotFound();

        $this->post(route('admin.edukasi.materi.terbitkan', $materi))->assertSessionHas('sukses');
        $this->get("/edukasi/{$materi->slug}")->assertOk();

        $this->post(route('admin.edukasi.materi.tarik', $materi))->assertSessionHas('sukses');
        $this->get("/edukasi/{$materi->slug}")->assertNotFound();
    }

    public function test_pratinjau_menampilkan_draf_hanya_untuk_admin(): void
    {
        $materi = Materi::factory()->create(['judul' => 'Masih Draf']);
        Langkah::factory()->for($materi)->create(['judul' => 'Langkah Awal']);

        $this->get(route('admin.edukasi.materi.pratinjau', $materi))->assertOk()
            ->assertSee('Ini pratinjau')
            ->assertSee('Warga belum bisa melihat')
            ->assertSee('Masih Draf')
            ->assertSee('Langkah Awal')
            ->assertSee('noindex');

        $this->actingAs(User::factory()->create());
        $this->get(route('admin.edukasi.materi.pratinjau', $materi))->assertForbidden();
    }

    public function test_berkas_berbahaya_atau_terlalu_besar_ditolak(): void
    {
        $this->post(route('admin.edukasi.materi.store'), $this->isian([
            'gambar' => UploadedFile::fake()->createWithContent('serangan.php', '<?php echo 1;'),
        ]))->assertSessionHasErrors('gambar');

        $this->post(route('admin.edukasi.materi.store'), $this->isian([
            'gambar' => UploadedFile::fake()->createWithContent('gambar.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        ]))->assertSessionHasErrors('gambar');

        // Berakhiran .jpg tetapi isinya bukan gambar.
        $this->post(route('admin.edukasi.materi.store'), $this->isian([
            'gambar' => UploadedFile::fake()->createWithContent('palsu.jpg', 'bukan gambar'),
        ]))->assertSessionHasErrors('gambar');

        $this->post(route('admin.edukasi.materi.store'), $this->isian([
            'gambar' => UploadedFile::fake()->create('besar.jpg', 6000, 'image/jpeg'),
        ]))->assertSessionHasErrors('gambar');

        $this->assertDatabaseCount('edukasi_materi', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    #[RequiresPhpExtension('gd')]
    public function test_gambar_utama_disimpan_diganti_dan_dihapus(): void
    {
        $this->post(route('admin.edukasi.materi.store'), $this->isian([
            'gambar' => UploadedFile::fake()->image('a.jpg', 100, 100),
        ]))->assertSessionHasNoErrors();

        $materi = Materi::query()->sole();
        $pertama = $materi->gambar;
        Storage::disk('public')->assertExists($pertama);

        $dasar = ['kategori_id' => $materi->kategori_id, 'judul' => $materi->judul, 'ringkasan' => $materi->ringkasan];

        // Menyimpan tanpa memilih berkas tidak boleh menghilangkan gambar.
        $this->put(route('admin.edukasi.materi.update', $materi), $dasar)->assertSessionHasNoErrors();
        $this->assertSame($pertama, $materi->refresh()->gambar);

        $this->put(route('admin.edukasi.materi.update', $materi), $dasar + ['gambar' => UploadedFile::fake()->image('b.png', 100, 100)]);
        $kedua = $materi->refresh()->gambar;
        $this->assertNotSame($pertama, $kedua);
        Storage::disk('public')->assertMissing($pertama);
        Storage::disk('public')->assertExists($kedua);

        $this->put(route('admin.edukasi.materi.update', $materi), $dasar + ['hapus_gambar' => '1']);
        $this->assertNull($materi->refresh()->gambar);
        Storage::disk('public')->assertMissing($kedua);
    }

    public function test_menghapus_materi_ikut_menghapus_langkah_dan_semua_gambarnya(): void
    {
        $disk = Storage::disk('public');
        $disk->put('edukasi/utama.webp', 'x');
        $disk->put('edukasi/langkah.webp', 'x');
        $materi = Materi::factory()->create(['gambar' => 'edukasi/utama.webp']);
        Langkah::factory()->for($materi)->create(['gambar' => 'edukasi/langkah.webp']);
        Langkah::factory()->for($materi)->create();

        $this->delete(route('admin.edukasi.materi.destroy', $materi))->assertRedirect(route('admin.edukasi.materi.index'));

        $this->assertDatabaseCount('edukasi_materi', 0);
        $this->assertDatabaseCount('edukasi_langkah', 0);
        $this->assertSame([], $disk->allFiles());
    }

    public function test_tautan_youtube_disimpan_sebagai_kode_dan_tautan_asing_ditolak(): void
    {
        $this->post(route('admin.edukasi.materi.store'), $this->isian(['video_youtube' => 'https://youtu.be/dQw4w9WgXcQ?si=x']))->assertSessionHasNoErrors();
        $this->assertSame('dQw4w9WgXcQ', Materi::query()->sole()->video_youtube);

        $this->post(route('admin.edukasi.materi.store'), $this->isian(['judul' => 'Lain', 'video_youtube' => 'https://contoh.com/video']))
            ->assertSessionHasErrors('video_youtube');
        $this->assertDatabaseCount('edukasi_materi', 1);
    }
}
