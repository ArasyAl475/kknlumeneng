<?php

namespace Tests\Feature\Profil\Admin;

use App\Models\Profil\Berita;
use App\Models\Profil\Foto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BeritaAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create(['name' => 'Admin Lumeneng']));
    }

    /**
     * @return array<string, mixed>
     */
    private function isian(array $tambahan = []): array
    {
        return $tambahan + [
            'jenis' => 'berita',
            'judul' => 'Kerja bakti dusun',
            'ringkasan' => 'Warga membersihkan saluran air.',
            'isi' => "Paragraf satu.\n\nParagraf dua.",
            'status' => 'terbit',
        ];
    }

    public function test_formulir_baru_mengisi_nama_penulis(): void
    {
        $this->get(route('admin.profil.berita.create'))->assertOk()->assertSee('value="Admin Lumeneng"', false);
    }

    public function test_terbitkan_langsung(): void
    {
        $this->post(route('admin.profil.berita.store'), $this->isian())
            ->assertRedirect(route('admin.profil.berita.index'))
            ->assertSessionHas('sukses');

        $berita = Berita::query()->sole();
        $this->assertSame('kerja-bakti-dusun', $berita->slug);
        $this->assertTrue($berita->sudahTerbit());
        $this->get(route('berita.show', $berita->slug))->assertOk();
    }

    public function test_draf_tidak_terlihat_warga_tetapi_bisa_dipratinjau(): void
    {
        $this->post(route('admin.profil.berita.store'), $this->isian(['status' => 'draf']));

        $berita = Berita::query()->sole();
        $this->assertFalse($berita->sudahTerbit());
        $this->get(route('admin.profil.berita.index'))->assertSee('Draf');

        $this->get(route('admin.profil.berita.pratinjau', $berita))->assertOk()
            ->assertSee('Ini pratinjau.')
            ->assertSee('Warga belum bisa melihat tulisan ini.')
            ->assertSee('noindex', false);
    }

    public function test_judul_sama_mendapat_alamat_berbeda(): void
    {
        $this->post(route('admin.profil.berita.store'), $this->isian());
        $this->post(route('admin.profil.berita.store'), $this->isian());

        $this->assertSame(['kerja-bakti-dusun', 'kerja-bakti-dusun-2'], Berita::query()->orderBy('id')->pluck('slug')->all());
    }

    public function test_ubah_mempertahankan_slug_dan_tanggal_terbit(): void
    {
        $berita = Berita::factory()->create(['slug' => 'alamat-lama', 'diterbitkan_pada' => now()->subDays(5)->startOfMinute()]);
        $tanggal = $berita->diterbitkan_pada;

        $this->put(route('admin.profil.berita.update', $berita), $this->isian(['judul' => 'Judul baru']))
            ->assertRedirect(route('admin.profil.berita.edit', $berita));

        $berita->refresh();
        $this->assertSame('Judul baru', $berita->judul);
        $this->assertSame('alamat-lama', $berita->slug);
        $this->assertTrue($tanggal->equalTo($berita->diterbitkan_pada));
    }

    public function test_jadikan_draf_menyembunyikan_dari_warga(): void
    {
        $berita = Berita::factory()->create();

        $this->put(route('admin.profil.berita.update', $berita), $this->isian(['status' => 'draf']));

        $this->assertFalse($berita->refresh()->sudahTerbit());
        $this->get(route('berita.show', $berita->slug))->assertNotFound();
    }

    public function test_foto_diunggah_dan_terhapus_bersama_berita(): void
    {
        Storage::fake('public');

        $this->post(route('admin.profil.berita.store'), $this->isian(['gambar' => UploadedFile::fake()->image('foto.jpg', 1600, 900)]));

        $berita = Berita::query()->sole();
        Storage::disk('public')->assertExists($berita->gambar);

        $this->delete(route('admin.profil.berita.destroy', $berita))->assertRedirect(route('admin.profil.berita.index'));

        $this->assertDatabaseCount('profil_berita', 0);
        Storage::disk('public')->assertMissing($berita->gambar);
    }

    public function test_foto_kegiatan_masuk_galeri_dan_terhubung(): void
    {
        Storage::fake('public');

        $this->post(route('admin.profil.berita.store'), $this->isian([
            'foto_kegiatan' => [UploadedFile::fake()->image('1.jpg'), UploadedFile::fake()->image('2.jpg')],
        ]))->assertSessionHasNoErrors();

        $berita = Berita::query()->sole();
        $this->assertSame(2, $berita->foto()->count());

        // Menyimpan ulang dengan foto baru menambah, bukan mengganti.
        $this->put(route('admin.profil.berita.update', $berita), $this->isian(['foto_kegiatan' => [UploadedFile::fake()->image('3.jpg')]]));
        $this->assertSame([1, 2, 3], $berita->foto()->pluck('urutan')->all());

        $this->get(route('admin.profil.berita.edit', $berita))->assertOk()->assertSee('Foto dokumentasi (3)');
    }

    public function test_hapus_berita_ikut_menghapus_foto_dokumentasi(): void
    {
        Storage::fake('public');
        $this->post(route('admin.profil.berita.store'), $this->isian(['foto_kegiatan' => [UploadedFile::fake()->image('1.jpg')]]));
        $berita = Berita::query()->sole();
        $jalur = $berita->foto()->value('gambar');
        $fotoMandiri = Foto::factory()->create();

        $this->delete(route('admin.profil.berita.destroy', $berita));

        $this->assertSame([$fotoMandiri->id], Foto::query()->pluck('id')->all());
        Storage::disk('public')->assertMissing($jalur);
    }

    public function test_isi_berformat_dibersihkan_saat_disimpan(): void
    {
        $this->post(route('admin.profil.berita.store'), $this->isian([
            'isi' => '<p><strong>Penting</strong></p><script>alert(1)</script><p><a href="javascript:alert(2)">x</a></p>',
        ]))->assertSessionHasNoErrors();

        $isi = Berita::query()->sole()->isi;
        $this->assertStringContainsString('<strong>Penting</strong>', $isi);
        $this->assertStringNotContainsString('script', $isi);
        $this->assertStringNotContainsString('javascript:', $isi);
    }

    public function test_isi_editor_kosong_ditolak(): void
    {
        $this->post(route('admin.profil.berita.store'), $this->isian(['isi' => '<p><br></p>']))
            ->assertSessionHasErrors(['isi' => 'Isi tulisan masih kosong.']);
    }

    public function test_formulir_memuat_editor_dan_unggah_banyak_foto(): void
    {
        $this->get(route('admin.profil.berita.create'))->assertOk()
            ->assertSee('data-editor', false)
            ->assertSee('data-unggah="'.route('admin.profil.gambar-isi').'"', false)
            ->assertSee('name="foto_kegiatan[]"', false)
            ->assertSee('multiple', false);
    }

    public function test_isian_wajib_dan_pilihan_divalidasi(): void
    {
        $this->post(route('admin.profil.berita.store'), ['jenis' => 'gosip', 'status' => 'kapan-kapan'])
            ->assertSessionHasErrors(['jenis', 'judul', 'ringkasan', 'isi', 'status']);
    }

    public function test_daftar_bisa_disaring_dan_draf_paling_atas(): void
    {
        Berita::factory()->create(['judul' => 'Pengumuman terbit', 'jenis' => 'pengumuman']);
        Berita::factory()->draf()->create(['judul' => 'Berita draf', 'jenis' => 'berita']);
        Berita::factory()->create(['judul' => 'Berita terbit', 'jenis' => 'berita']);

        $this->get(route('admin.profil.berita.index'))->assertOk()->assertSeeInOrder(['Berita draf', 'Berita terbit']);

        $this->get(route('admin.profil.berita.index', ['jenis' => 'pengumuman']))->assertOk()
            ->assertSee('Pengumuman terbit')->assertDontSee('Berita draf');
    }
}
