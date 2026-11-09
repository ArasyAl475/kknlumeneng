<?php

namespace Tests\Feature\Profil\Admin;

use App\Models\Profil\Berita;
use App\Models\Profil\Foto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GaleriAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_unggah_banyak_foto_mandiri(): void
    {
        $this->get(route('admin.profil.galeri.create'))->assertOk()->assertSee('Tambah foto desa');

        $this->post(route('admin.profil.galeri.store'), [
            'foto' => [UploadedFile::fake()->image('a.jpg', 800, 600), UploadedFile::fake()->image('b.png', 600, 800)],
            'judul' => 'Panen padi',
            'tanggal' => '2026-10-01',
        ])->assertRedirect(route('admin.profil.galeri.index'))->assertSessionHas('sukses', '2 foto ditambahkan ke galeri.');

        $foto = Foto::query()->orderBy('id')->get();
        $this->assertCount(2, $foto);
        $this->assertNull($foto[0]->berita_id);
        $this->assertSame('Panen padi', $foto[1]->judul);
        $this->assertSame([1, 2], $foto->pluck('urutan')->all());
        $foto->each(fn (Foto $f) => Storage::disk('public')->assertExists($f->gambar));

        $this->get(route('admin.profil.galeri.index'))->assertOk()->assertSee('Foto desa (mandiri)');
    }

    public function test_menu_galeri_hanya_untuk_foto_mandiri_tanpa_pilihan_kegiatan(): void
    {
        $berita = Berita::factory()->create(['judul' => 'Kerja bakti']);

        $this->get(route('admin.profil.galeri.create'))->assertOk()
            ->assertDontSee('name="berita_id"', false)
            ->assertSee('Foto dokumentasi kegiatan');

        // Walau dikirim paksa, foto dari menu Galeri tetap menjadi foto mandiri.
        $this->post(route('admin.profil.galeri.store'), [
            'foto' => [UploadedFile::fake()->image('a.jpg')],
            'berita_id' => $berita->id,
        ])->assertSessionHasNoErrors();

        $this->assertNull(Foto::query()->sole()->berita_id);
    }

    public function test_ubah_foto_kegiatan_tidak_memutus_sambungan_dan_bisa_ganti_foto(): void
    {
        $berita = Berita::factory()->create(['judul' => 'Kerja bakti dusun']);
        $foto = Foto::factory()->create(['berita_id' => $berita->id, 'gambar' => UploadedFile::fake()->image('lama.jpg')->store('profil', 'public')]);
        $lama = $foto->gambar;

        $this->get(route('admin.profil.galeri.edit', $foto))->assertOk()
            ->assertSee('Foto ini dokumentasi dari')
            ->assertSee('Kerja bakti dusun')
            ->assertDontSee('name="berita_id"', false);

        $this->put(route('admin.profil.galeri.update', $foto), [
            'berita_id' => '',
            'judul' => 'Judul foto baru',
            'urutan' => '4',
            'foto' => UploadedFile::fake()->image('baru.jpg'),
        ])->assertRedirect(route('admin.profil.galeri.edit', $foto));

        $foto->refresh();
        $this->assertSame($berita->id, $foto->berita_id);
        $this->assertSame('Judul foto baru', $foto->judul);
        $this->assertSame(4, $foto->urutan);
        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($foto->gambar);
    }

    public function test_hapus_foto_kembali_ke_halaman_asal(): void
    {
        $berita = Berita::factory()->create();
        $foto = Foto::factory()->create(['berita_id' => $berita->id, 'gambar' => UploadedFile::fake()->image('a.jpg')->store('profil', 'public')]);

        $this->from(route('admin.profil.berita.edit', $berita))
            ->delete(route('admin.profil.galeri.destroy', $foto))
            ->assertRedirect(route('admin.profil.berita.edit', $berita));

        $this->assertDatabaseCount('profil_galeri', 0);
        Storage::disk('public')->assertMissing($foto->gambar);
    }

    public function test_validasi_unggah(): void
    {
        $this->post(route('admin.profil.galeri.store'), [])->assertSessionHasErrors('foto');

        $this->post(route('admin.profil.galeri.store'), [
            'foto' => [UploadedFile::fake()->create('bukan.pdf', 10, 'application/pdf')],
            'tanggal' => now()->addDays(3)->toDateString(),
        ])->assertSessionHasErrors(['foto.0', 'tanggal']);
    }

    public function test_sisip_foto_dari_editor_mengembalikan_alamat_relatif(): void
    {
        $jawaban = $this->postJson(route('admin.profil.gambar-isi'), ['gambar' => UploadedFile::fake()->image('isi.jpg', 2000, 1500)])
            ->assertCreated()
            ->assertJsonStructure(['url']);

        $url = $jawaban->json('url');
        $this->assertStringStartsWith('/storage/profil/', $url);
        Storage::disk('public')->assertExists(substr($url, strlen('/storage/')));
    }

    public function test_sisip_berkas_bukan_gambar_ditolak_dengan_pesan(): void
    {
        $this->postJson(route('admin.profil.gambar-isi'), ['gambar' => UploadedFile::fake()->create('virus.php', 5, 'text/plain')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('gambar');
    }
}
