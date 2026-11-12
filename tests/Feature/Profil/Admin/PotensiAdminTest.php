<?php

namespace Tests\Feature\Profil\Admin;

use App\Http\Requests\Profil\PotensiRequest;
use App\Models\Profil\Foto;
use App\Models\Profil\Potensi;
use App\Models\User;
use Database\Seeders\ProfilSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PotensiAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
    }

    private function foto(string $nama = 'a.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($nama, 800, 600);
    }

    private function isianDasar(array $tambahan = []): array
    {
        return $tambahan + ['nama' => 'Kebun kopi Sikembang', 'kategori' => 'perkebunan', 'hasil' => 'Kopi, pisang', 'lokasi' => 'Dusun VI Sikembang', 'luas' => '4 ha', 'keterangan' => 'Kopi rakyat.', 'urutan' => '2'];
    }

    public function test_tambah_potensi_dengan_banyak_foto_otomatis_masuk_galeri(): void
    {
        $this->get(route('admin.profil.potensi.create'))->assertOk()->assertSee('Jenis potensi')->assertSee('Lokasi: di mana')->assertSee('name="fotos[]"', false);

        $this->post(route('admin.profil.potensi.store'), $this->isianDasar(['fotos' => [$this->foto('1.jpg'), $this->foto('2.jpg'), $this->foto('3.jpg')]]))
            ->assertRedirect(route('admin.profil.potensi.index'))
            ->assertSessionHas('sukses', fn ($pesan) => str_contains($pesan, '3 foto baru juga masuk ke Galeri'));

        $potensi = Potensi::query()->sole();
        $this->assertSame(['Kopi', 'pisang'], $potensi->daftarHasil());
        $this->assertCount(3, $potensi->fotos);
        $this->assertSame([1, 2, 3], $potensi->fotos->pluck('urutan')->all());

        foreach ($potensi->fotos as $foto) {
            Storage::disk('public')->assertExists($foto->gambar);
            $this->assertNull($foto->berita_id);
            $this->assertSame('Kebun kopi Sikembang', $foto->judul);
        }

        // Tersambung ke galeri: muncul di halaman Galeri publik dan menu admin Galeri.
        $this->assertSame(3, Foto::query()->publik()->count());
        $this->get(route('galeri.index'))->assertOk()->assertSee('Kebun kopi Sikembang');
        $this->get(route('admin.profil.potensi.index'))->assertOk()->assertSee('Kebun kopi Sikembang')->assertSee('3 foto');
    }

    public function test_ubah_potensi_menambah_menghapus_dan_memilih_foto_utama(): void
    {
        $this->post(route('admin.profil.potensi.store'), $this->isianDasar(['fotos' => [$this->foto('1.jpg'), $this->foto('2.jpg'), $this->foto('3.jpg')]]));
        $potensi = Potensi::query()->sole();
        [$satu, $dua, $tiga] = $potensi->fotos->all();

        $this->get(route('admin.profil.potensi.edit', $potensi))->assertOk()->assertSee('Hapus foto ini')->assertSee('Jadikan foto utama');

        $this->put(route('admin.profil.potensi.update', $potensi), $this->isianDasar([
            'nama' => 'Kebun kopi Baru',
            'hapus_foto' => [$satu->id],
            'utama' => $tiga->id,
            'fotos' => [$this->foto('4.jpg')],
        ]))->assertRedirect(route('admin.profil.potensi.index'));

        $potensi->refresh();
        $this->assertDatabaseMissing('profil_galeri', ['id' => $satu->id]);
        Storage::disk('public')->assertMissing($satu->gambar);

        // Foto ke-3 jadi pertama; sisanya mengikuti, foto baru di akhir; urutan dirapikan 1..3.
        $urut = $potensi->fotos()->get();
        $this->assertCount(3, $urut);
        $this->assertSame($tiga->id, $urut[0]->id);
        $this->assertSame($dua->id, $urut[1]->id);
        $this->assertSame([1, 2, 3], $urut->pluck('urutan')->all());
        // Judul yang masih nama lama ikut berganti.
        $this->assertSame('Kebun kopi Baru', $urut[0]->judul);
    }

    public function test_hanya_foto_milik_potensi_itu_yang_bisa_dihapus(): void
    {
        $this->post(route('admin.profil.potensi.store'), $this->isianDasar(['nama' => 'Satu', 'fotos' => [$this->foto()]]));
        $this->post(route('admin.profil.potensi.store'), $this->isianDasar(['nama' => 'Dua', 'fotos' => [$this->foto('b.jpg')]]));
        $satu = Potensi::query()->where('nama', 'Satu')->sole();
        $milikDua = Potensi::query()->where('nama', 'Dua')->sole()->fotos->first();

        $this->put(route('admin.profil.potensi.update', $satu), $this->isianDasar(['nama' => 'Satu', 'hapus_foto' => [$milikDua->id]]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('profil_galeri', ['id' => $milikDua->id]);
        Storage::disk('public')->assertExists($milikDua->gambar);
    }

    public function test_batas_total_foto_per_potensi(): void
    {
        $batas = PotensiRequest::MAKS_FOTO_TOTAL;
        $potensi = Potensi::factory()->create();
        Foto::factory()->count($batas)->create(['potensi_id' => $potensi->id]);

        $this->put(route('admin.profil.potensi.update', $potensi), $this->isianDasar(['nama' => $potensi->nama, 'fotos' => [$this->foto()]]))
            ->assertSessionHasErrors('fotos');

        // Menghapus satu foto lama memberi tempat untuk satu foto baru.
        $this->put(route('admin.profil.potensi.update', $potensi), $this->isianDasar([
            'nama' => $potensi->nama, 'fotos' => [$this->foto()], 'hapus_foto' => [$potensi->fotos()->first()->id],
        ]))->assertSessionHasNoErrors();
        $this->assertSame($batas, $potensi->fotos()->count());
    }

    public function test_hapus_potensi_menghapus_foto_dan_berkasnya_juga_dari_galeri(): void
    {
        $this->post(route('admin.profil.potensi.store'), $this->isianDasar(['fotos' => [$this->foto(), $this->foto('b.jpg')]]));
        $potensi = Potensi::query()->sole();
        $berkas = $potensi->fotos->pluck('gambar');

        $this->delete(route('admin.profil.potensi.destroy', $potensi))->assertRedirect(route('admin.profil.potensi.index'));

        $this->assertModelMissing($potensi);
        $this->assertDatabaseCount('profil_galeri', 0);
        $berkas->each(fn ($b) => Storage::disk('public')->assertMissing($b));
    }

    #[DataProvider('isianSalah')]
    public function test_isian_tidak_valid_ditolak(array $isian, string $kolom): void
    {
        $this->post(route('admin.profil.potensi.store'), $isian + ['nama' => 'X', 'kategori' => 'hutan'])->assertSessionHasErrors($kolom);

        $this->assertDatabaseCount('profil_potensi', 0);
    }

    public static function isianSalah(): array
    {
        return [
            'nama kosong' => [['nama' => ''], 'nama'],
            'jenis tidak dikenal' => [['kategori' => 'lain'], 'kategori'],
            'jenis kosong' => [['kategori' => ''], 'kategori'],
            'urutan negatif' => [['urutan' => '-1'], 'urutan'],
            'foto bukan gambar' => [['fotos' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]], 'fotos.0'],
            'terlalu banyak foto sekali simpan' => [['fotos' => array_fill(0, 11, UploadedFile::fake()->image('a.jpg'))], 'fotos'],
        ];
    }

    public function test_halaman_profil_menampilkan_potensi_dengan_kolase_foto_dan_tab(): void
    {
        $potensi = Potensi::factory()->create([
            'nama' => 'Sawah terasering', 'kategori' => 'pertanian', 'hasil' => 'Padi, jagung',
            'lokasi' => 'Bagian utara desa', 'luas' => '70,85 ha', 'urutan' => 1,
        ]);
        Foto::factory()->count(4)->create(['potensi_id' => $potensi->id, 'judul' => 'Sawah terasering']);

        $this->get(route('profil.index'))->assertOk()
            ->assertSee('Potensi &amp; penghasil desa', false)
            ->assertSee('Sawah terasering')
            ->assertSee('Sawah &amp; ladang', false)
            ->assertSee('Padi')
            ->assertSee('jagung')
            ->assertSee('Bagian utara desa')
            ->assertSee('70,85 ha')
            ->assertSee('href="#potensi"', false)
            ->assertSee('data-galeri', false)
            // Kolase 4 foto: foto utama 4x4 di kiri atas, sesuai SusunanKolase.
            ->assertSee('grid-area: 1 / 1 / 5 / 5', false);
    }

    public function test_foto_di_atas_enam_tidak_digambar_tetapi_tetap_ada_di_penampil(): void
    {
        $potensi = Potensi::factory()->create(['nama' => 'Hutan banyak foto']);
        Foto::factory()->count(8)->create(['potensi_id' => $potensi->id]);

        $html = $this->get(route('profil.index'))->assertOk()->assertSee('+2', false)->getContent();

        $this->assertSame(8, preg_match_all('/<a [^>]*\bdata-foto\b/', $html));
        $this->assertSame(2, preg_match_all('/<a [^>]*\bdata-foto hidden\b/', $html));
    }

    public function test_potensi_tanpa_foto_memakai_ikon_pengganti(): void
    {
        Potensi::factory()->create(['nama' => 'Potensi tanpa foto', 'luas' => '5 ha']);

        $this->get(route('profil.index'))->assertOk()->assertSee('Potensi tanpa foto')->assertSee('5 ha')->assertDontSee('data-galeri', false);
    }

    public function test_tanpa_potensi_bagian_dan_tabnya_disembunyikan(): void
    {
        $this->get(route('profil.index'))->assertOk()->assertDontSee('Potensi &amp; penghasil desa', false)->assertDontSee('href="#potensi"', false);
    }

    public function test_nama_potensi_aman_dari_html(): void
    {
        Potensi::factory()->create(['nama' => '<script>alert(1)</script>']);

        $this->get(route('profil.index'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_seeder_mengisi_foto_potensi_ke_galeri_tanpa_duplikat(): void
    {
        $this->seed(ProfilSeeder::class);

        $jumlah = fn (string $nama) => Potensi::query()->where('nama', $nama)->sole()->fotos()->count();
        $this->assertSame(5, $jumlah('Hutan lindung'));
        $this->assertSame(4, $jumlah('Kebun campuran'));
        $this->assertSame(3, $jumlah('Sawah terasering'));
        $this->assertSame(5, $jumlah('Mata air desa'));
        $this->assertSame(3, $jumlah('Kali Genteng'));

        $foto = Foto::query()->whereNotNull('potensi_id')->first();
        Storage::disk('public')->assertExists($foto->gambar);
        $this->assertStringStartsWith('profil/kajian/', $foto->gambar);

        $total = Foto::query()->count();
        $this->seed(ProfilSeeder::class);
        $this->assertSame($total, Foto::query()->count());

        $this->get(route('profil.index'))->assertSee('Kajian Pemetaan Bencana Desa Lumeneng 2024');
    }
}
