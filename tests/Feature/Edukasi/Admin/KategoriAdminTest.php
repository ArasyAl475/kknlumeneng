<?php

namespace Tests\Feature\Edukasi\Admin;

use App\Models\Edukasi\Kategori;
use App\Models\Edukasi\Materi;
use App\Models\User;
use Database\Seeders\EdukasiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_halaman_kategori_tampil_dengan_menu_edukasi(): void
    {
        Kategori::factory()->create(['nama' => 'Fermentasi']);

        $this->get(route('admin.edukasi.kategori.index'))->assertOk()->assertSee('Fermentasi')->assertSee('Kategori materi');
        $this->get(route('admin.edukasi.kategori.create'))->assertOk();
    }

    public function test_menambah_dan_mengubah_kategori_tanpa_mengubah_alamat(): void
    {
        $this->post(route('admin.edukasi.kategori.store'), ['nama' => 'Mitigasi Longsor'])->assertRedirect(route('admin.edukasi.kategori.index'));

        $kategori = Kategori::query()->sole();
        $this->assertSame('mitigasi-longsor', $kategori->slug);

        $this->get(route('admin.edukasi.kategori.edit', $kategori))->assertOk();
        $this->put(route('admin.edukasi.kategori.update', $kategori), ['nama' => 'Bencana Longsor'])->assertRedirect();

        $kategori->refresh();
        $this->assertSame('Bencana Longsor', $kategori->nama);
        $this->assertSame('mitigasi-longsor', $kategori->slug);
    }

    public function test_tips_sirkular_bisa_diisi_diubah_dan_dibatasi_panjangnya(): void
    {
        $this->post(route('admin.edukasi.kategori.store'), ['nama' => 'Kompos', 'tips_sirkular' => 'Pakai ulang sisa dapur.'])->assertSessionHasNoErrors();
        $kategori = Kategori::query()->sole();
        $this->assertSame('Pakai ulang sisa dapur.', $kategori->tips_sirkular);

        $this->put(route('admin.edukasi.kategori.update', $kategori), ['nama' => 'Kompos', 'tips_sirkular' => str_repeat('a', 501)])->assertSessionHasErrors('tips_sirkular');
        $this->put(route('admin.edukasi.kategori.update', $kategori), ['nama' => 'Kompos', 'tips_sirkular' => ''])->assertSessionHasNoErrors();
        $this->assertNull($kategori->refresh()->tips_sirkular);
    }

    public function test_seeder_mengisi_tips_tanpa_menimpa_tulisan_admin(): void
    {
        $this->seed(EdukasiSeeder::class);
        $this->assertNotNull(Kategori::query()->where('slug', 'jamur')->value('tips_sirkular'));

        Kategori::query()->where('slug', 'jamur')->update(['tips_sirkular' => 'Tulisan admin']);
        $this->seed(EdukasiSeeder::class);
        $this->assertSame('Tulisan admin', Kategori::query()->where('slug', 'jamur')->value('tips_sirkular'));
    }

    public function test_nama_kategori_tidak_boleh_kembar(): void
    {
        $kategori = Kategori::factory()->create(['nama' => 'Fermentasi']);

        $this->post(route('admin.edukasi.kategori.store'), ['nama' => 'Fermentasi'])->assertSessionHasErrors('nama');
        // Menyimpan ulang tanpa mengganti nama tidak boleh dianggap kembar dengan dirinya sendiri.
        $this->put(route('admin.edukasi.kategori.update', $kategori), ['nama' => 'Fermentasi'])->assertSessionHasNoErrors();
    }

    public function test_kategori_berisi_materi_tidak_bisa_dihapus(): void
    {
        $materi = Materi::factory()->create();

        $this->delete(route('admin.edukasi.kategori.destroy', $materi->kategori_id))->assertSessionHas('galat');

        $this->assertDatabaseHas('edukasi_materi', ['id' => $materi->id]);
        $this->assertDatabaseHas('edukasi_kategori', ['id' => $materi->kategori_id]);
    }

    public function test_kategori_kosong_bisa_dihapus(): void
    {
        $kategori = Kategori::factory()->create();

        $this->delete(route('admin.edukasi.kategori.destroy', $kategori))->assertSessionHas('sukses');

        $this->assertDatabaseMissing('edukasi_kategori', ['id' => $kategori->id]);
    }

    public function test_seeder_membuat_kategori_awal_tanpa_materi_dan_aman_diulang(): void
    {
        $this->seed(EdukasiSeeder::class);
        Kategori::query()->where('slug', 'baglog')->update(['nama' => 'Baglog Jamur']);
        $this->seed(EdukasiSeeder::class);

        $this->assertDatabaseCount('edukasi_kategori', 3);
        $this->assertDatabaseHas('edukasi_kategori', ['slug' => 'baglog', 'nama' => 'Baglog Jamur']);
        $this->assertDatabaseCount('edukasi_materi', 0);
    }
}
