<?php

namespace Tests\Feature\Profil\Admin;

use App\Models\Profil\Pengaturan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengaturanAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_empat_formulir_bisa_dibuka(): void
    {
        foreach (['identitas' => 'Identitas & sambutan', 'sejarah' => 'Sejarah, visi & misi', 'wilayah' => 'Data wilayah', 'kontak' => 'Kontak kantor desa'] as $bagian => $judul) {
            $this->get(route("admin.profil.{$bagian}.edit"))->assertOk()->assertSee($judul)
                ->assertSee('action="'.route("admin.profil.{$bagian}.update").'"', false);
        }
    }

    public function test_simpan_sejarah_visi_misi(): void
    {
        $this->put(route('admin.profil.sejarah.update'), ['visi' => 'Visi baru', 'misi' => "Satu\nDua", 'sejarah' => 'Cerita'])
            ->assertRedirect(route('admin.profil.sejarah.edit'))
            ->assertSessionHas('sukses');

        $this->assertSame('Visi baru', Pengaturan::semua()['visi']);
        $this->assertSame("Satu\nDua", Pengaturan::semua()['misi']);
    }

    public function test_sambutan_dan_sejarah_berformat_dibersihkan(): void
    {
        $this->put(route('admin.profil.identitas.update'), [
            'nama_desa' => 'Lumeneng',
            'sambutan' => '<p><em>Sugeng rawuh</em></p><img src="x" onerror="alert(1)">',
        ])->assertSessionHasNoErrors();
        $this->put(route('admin.profil.sejarah.update'), ['sejarah' => '<p><br></p>'])->assertSessionHasNoErrors();

        $data = Pengaturan::semua();
        $this->assertStringContainsString('<em>Sugeng rawuh</em>', $data['sambutan']);
        $this->assertStringNotContainsString('onerror', $data['sambutan']);
        // Editor kosong tersimpan sebagai kosong, sehingga bagian Sejarah disembunyikan.
        $this->assertSame('', $data['sejarah']);

        $this->get(route('admin.profil.identitas.edit'))->assertOk()->assertSee('data-editor', false);
    }

    public function test_bagian_lain_tidak_ikut_tertimpa(): void
    {
        Pengaturan::simpan(['visi' => 'Tetap']);

        $this->put(route('admin.profil.kontak.update'), ['visi' => 'Disusupkan', 'telepon' => '0812 345'])->assertSessionHasNoErrors();

        $this->assertSame('Tetap', Pengaturan::semua()['visi']);
        $this->assertSame('0812 345', Pengaturan::semua()['telepon']);
    }

    public function test_angka_gaya_indonesia_diterima(): void
    {
        $this->put(route('admin.profil.wilayah.update'), ['jumlah_penduduk' => '3.412', 'luas_wilayah' => '1.642,5', 'jumlah_rt' => ''])
            ->assertSessionHasNoErrors();

        $data = Pengaturan::semua();
        $this->assertSame('3412', $data['jumlah_penduduk']);
        $this->assertSame('1642.5', $data['luas_wilayah']);
        $this->assertSame('', $data['jumlah_rt']);

        $this->get(route('profil.index'))->assertSee('3.412')->assertSee('1.642,5');
    }

    public function test_angka_berisi_huruf_ditolak(): void
    {
        $this->put(route('admin.profil.wilayah.update'), ['jumlah_penduduk' => 'tiga ribu'])
            ->assertSessionHasErrors('jumlah_penduduk');
    }

    public function test_kontak_divalidasi(): void
    {
        $this->put(route('admin.profil.kontak.update'), ['telepon' => 'telp: abc', 'email' => 'bukan-surel', 'tautan_peta' => 'javascript:alert(1)'])
            ->assertSessionHasErrors(['telepon', 'email', 'tautan_peta']);
    }

    public function test_nama_desa_wajib_dan_dikosongkan_dihormati(): void
    {
        $this->put(route('admin.profil.identitas.update'), ['nama_desa' => ''])->assertSessionHasErrors('nama_desa');

        $this->put(route('admin.profil.identitas.update'), ['nama_desa' => 'Lumeneng', 'kecamatan' => ''])->assertSessionHasNoErrors();
        // Isian yang sengaja dikosongkan tidak kembali ke nilai bawaan.
        $this->assertSame('', Pengaturan::semua()['kecamatan']);
    }

    public function test_foto_profil_kini_diatur_di_desain_web(): void
    {
        Storage::fake('public');

        $this->get(route('admin.profil.identitas.edit'))->assertOk()->assertSee(route('admin.desain.index'), false);

        // Unggahan lewat formulir lama diabaikan; foto hanya bisa diganti dari menu Desain Web.
        $this->put(route('admin.profil.identitas.update'), [
            'nama_desa' => 'Lumeneng',
            'foto_kepala_desa' => UploadedFile::fake()->image('kades.jpg', 800, 1000),
        ])->assertSessionHasNoErrors();

        $this->assertSame('', Pengaturan::semua()['foto_kepala_desa']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
