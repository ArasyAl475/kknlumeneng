<?php

namespace Tests\Feature\Edukasi\Admin;

use App\Models\Edukasi\Langkah;
use App\Models\Edukasi\Materi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

class LangkahAdminTest extends TestCase
{
    use RefreshDatabase;

    private Materi $materi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
        Storage::fake('public');
        $this->materi = Materi::factory()->create();
    }

    /**
     * @return list<string>
     */
    private function urutanJudul(): array
    {
        return $this->materi->langkah()->pluck('judul')->all();
    }

    private function buatTigaLangkah(): array
    {
        return [
            Langkah::factory()->for($this->materi)->create(['urutan' => 1, 'judul' => 'A']),
            Langkah::factory()->for($this->materi)->create(['urutan' => 2, 'judul' => 'B']),
            Langkah::factory()->for($this->materi)->create(['urutan' => 3, 'judul' => 'C']),
        ];
    }

    public function test_langkah_baru_masuk_paling_akhir(): void
    {
        $this->get(route('admin.edukasi.langkah.create', $this->materi))->assertOk();

        foreach (['Cuci', 'Rebus'] as $judul) {
            $this->post(route('admin.edukasi.langkah.store', $this->materi), ['judul' => $judul, 'isi' => 'Isi'])
                ->assertRedirect(route('admin.edukasi.materi.edit', $this->materi).'#langkah');
        }

        $this->assertSame(['Cuci', 'Rebus'], $this->urutanJudul());
        $this->get(route('admin.edukasi.materi.edit', $this->materi))->assertOk()->assertSeeInOrder(['1. Cuci', '2. Rebus']);
    }

    public function test_judul_dan_isi_wajib_diisi(): void
    {
        $this->post(route('admin.edukasi.langkah.store', $this->materi), [])->assertSessionHasErrors(['judul', 'isi']);

        $this->assertDatabaseCount('edukasi_langkah', 0);
    }

    public function test_memindahkan_naik_dan_turun(): void
    {
        [$a, $b, $c] = $this->buatTigaLangkah();

        $this->post(route('admin.edukasi.langkah.pindah', $c), ['arah' => 'naik'])->assertSessionHas('sukses');
        $this->assertSame(['A', 'C', 'B'], $this->urutanJudul());

        $this->post(route('admin.edukasi.langkah.pindah', $a), ['arah' => 'turun']);
        $this->assertSame(['C', 'A', 'B'], $this->urutanJudul());
    }

    public function test_memindahkan_di_ujung_tidak_mengubah_apa_pun(): void
    {
        [$a, , $c] = $this->buatTigaLangkah();

        $this->post(route('admin.edukasi.langkah.pindah', $a), ['arah' => 'naik']);
        $this->post(route('admin.edukasi.langkah.pindah', $c), ['arah' => 'turun']);

        $this->assertSame(['A', 'B', 'C'], $this->urutanJudul());
    }

    public function test_memindahkan_tetap_benar_setelah_ada_langkah_terhapus(): void
    {
        [$a, $b, $c] = $this->buatTigaLangkah();

        $this->delete(route('admin.edukasi.langkah.destroy', $b))->assertSessionHas('sukses');
        $this->post(route('admin.edukasi.langkah.pindah', $c), ['arah' => 'naik']);

        $this->assertSame(['C', 'A'], $this->urutanJudul());
    }

    public function test_memindahkan_tidak_menyentuh_langkah_materi_lain(): void
    {
        $lain = Langkah::factory()->create(['urutan' => 1, 'judul' => 'Lain']);
        $sendiri = Langkah::factory()->for($this->materi)->create(['urutan' => 2, 'judul' => 'Sendiri']);

        $this->post(route('admin.edukasi.langkah.pindah', $sendiri), ['arah' => 'naik']);

        $this->assertSame(2, $sendiri->refresh()->urutan);
        $this->assertSame(1, $lain->refresh()->urutan);
    }

    public function test_arah_pindah_divalidasi(): void
    {
        [$a] = $this->buatTigaLangkah();

        $this->post(route('admin.edukasi.langkah.pindah', $a), ['arah' => 'samping'])->assertSessionHasErrors('arah');
    }

    public function test_mengubah_langkah_ikut_memperbarui_tanggal_materi(): void
    {
        $langkah = Langkah::factory()->for($this->materi)->create();
        Materi::query()->whereKey($this->materi->id)->update(['updated_at' => now()->subWeek()]);

        $this->get(route('admin.edukasi.langkah.edit', $langkah))->assertOk();
        $this->put(route('admin.edukasi.langkah.update', $langkah), ['judul' => 'Baru', 'isi' => 'Isi baru'])->assertSessionHasNoErrors();

        $this->assertSame('Baru', $langkah->refresh()->judul);
        $this->assertTrue($this->materi->refresh()->updated_at->isToday());
    }

    #[RequiresPhpExtension('gd')]
    public function test_gambar_langkah_disimpan_diganti_dan_dibersihkan(): void
    {
        $this->post(route('admin.edukasi.langkah.store', $this->materi), [
            'judul' => 'Foto', 'isi' => 'Isi', 'gambar' => UploadedFile::fake()->image('a.jpg', 50, 50),
        ])->assertSessionHasNoErrors();

        $langkah = Langkah::query()->sole();
        $pertama = $langkah->gambar;
        Storage::disk('public')->assertExists($pertama);

        $this->put(route('admin.edukasi.langkah.update', $langkah), [
            'judul' => 'Foto', 'isi' => 'Isi', 'gambar' => UploadedFile::fake()->image('b.jpg', 50, 50),
        ])->assertSessionHasNoErrors();
        $kedua = $langkah->refresh()->gambar;
        Storage::disk('public')->assertMissing($pertama);

        $this->delete(route('admin.edukasi.langkah.destroy', $langkah));
        Storage::disk('public')->assertMissing($kedua);
    }
}
