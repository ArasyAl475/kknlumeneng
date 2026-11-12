<?php

namespace Tests\Feature\Profil\Admin;

use App\Models\Profil\Lembaga;
use App\Models\Profil\Perangkat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StrukturAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_tambah_ubah_hapus_perangkat_dengan_foto(): void
    {
        Storage::fake('public');

        $this->get(route('admin.profil.perangkat.create'))->assertOk()->assertSee('Letak di bagan struktur');

        $this->post(route('admin.profil.perangkat.store'), [
            'nama' => 'Ahmad Fauzi',
            'jabatan' => 'Kaur Keuangan',
            'kelompok' => 'kaur',
            'urutan' => '1',
            'foto' => UploadedFile::fake()->image('ahmad.jpg', 400, 400),
        ])->assertRedirect(route('admin.profil.perangkat.index'))->assertSessionHas('sukses');

        $perangkat = Perangkat::query()->sole();
        Storage::disk('public')->assertExists($perangkat->foto);

        $this->get(route('admin.profil.perangkat.index'))->assertOk()->assertSee('Ahmad Fauzi')->assertSee('Kepala Urusan');

        $this->put(route('admin.profil.perangkat.update', $perangkat), [
            'nama' => 'Ahmad Fauzi, S.E.',
            'jabatan' => 'Kaur Keuangan',
            'kelompok' => 'kaur',
            'hapus_foto' => '1',
        ])->assertRedirect(route('admin.profil.perangkat.index'));

        $fotoLama = $perangkat->foto;
        $perangkat->refresh();
        $this->assertSame('Ahmad Fauzi, S.E.', $perangkat->nama);
        $this->assertNull($perangkat->foto);
        $this->assertSame(0, $perangkat->urutan);
        Storage::disk('public')->assertMissing($fotoLama);

        $this->delete(route('admin.profil.perangkat.destroy', $perangkat))->assertRedirect(route('admin.profil.perangkat.index'));
        $this->assertDatabaseCount('profil_perangkat', 0);
    }

    public function test_halaman_profil_menampilkan_kartu_berfoto_tegak_dan_kartu_tanpa_foto_seperti_semula(): void
    {
        Storage::fake('public');
        $this->post(route('admin.profil.perangkat.store'), [
            'nama' => 'Budi Berfoto', 'jabatan' => 'Kasi Pemerintahan', 'kelompok' => 'kasi',
            'foto' => UploadedFile::fake()->image('budi.jpg', 800, 1000),
        ]);
        Perangkat::factory()->create(['nama' => 'Citra Tanpafoto', 'jabatan' => 'Kasi Pelayanan', 'kelompok' => 'kasi']);
        $berfoto = Perangkat::query()->where('nama', 'Budi Berfoto')->sole();

        $html = $this->get(route('profil.index'))->assertOk()
            // Dengan foto: foto potret berbingkai 4:5, nama dan jabatan di bawahnya.
            ->assertSee('alt="Foto Budi Berfoto"', false)
            ->assertSee($berfoto->urlFoto(), false)
            ->assertSee('aspect-[4/5]', false)
            ->assertSeeInOrder(['Foto Budi Berfoto', 'Budi Berfoto', 'Kasi Pemerintahan'])
            // Tanpa foto: huruf awal nama, bukan gambar.
            ->assertSee('Citra Tanpafoto')
            ->assertDontSee('alt="Foto Citra Tanpafoto"', false)
            ->getContent();

        // Kartu tanpa foto selebar kolom (col-span-2), kartu berfoto setengah kolom.
        $this->assertSame(1, substr_count($html, '<li class="col-span-2">'));
    }

    public function test_kelompok_tak_dikenal_ditolak(): void
    {
        $this->post(route('admin.profil.perangkat.store'), ['nama' => 'X', 'jabatan' => 'Y', 'kelompok' => 'raja'])
            ->assertSessionHasErrors('kelompok');
    }

    public function test_inisial_mengabaikan_gelar(): void
    {
        $this->assertSame('SW', (new Perangkat(['nama' => 'H. Sri Wahyuni, S.E.']))->inisial());
        $this->assertSame('S', (new Perangkat(['nama' => 'Sugiarto']))->inisial());
    }

    public function test_tambah_ubah_hapus_lembaga(): void
    {
        $this->post(route('admin.profil.lembaga.store'), ['nama' => 'Badan Permusyawaratan Desa', 'singkatan' => 'BPD'])
            ->assertRedirect(route('admin.profil.lembaga.index'));

        $lembaga = Lembaga::query()->sole();
        $this->assertSame(0, $lembaga->urutan);

        $this->put(route('admin.profil.lembaga.update', $lembaga), ['nama' => 'BPD Lumeneng', 'ketua' => 'Pak Ketua', 'urutan' => '3'])
            ->assertRedirect(route('admin.profil.lembaga.index'));
        $this->assertSame('Pak Ketua', $lembaga->refresh()->ketua);

        $this->get(route('admin.profil.lembaga.index'))->assertOk()->assertSee('BPD Lumeneng');

        $this->delete(route('admin.profil.lembaga.destroy', $lembaga))->assertRedirect(route('admin.profil.lembaga.index'));
        $this->assertDatabaseCount('profil_lembaga', 0);
    }
}
