<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\FotoProduk;
use App\Models\Toko\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotoProdukTest extends TestCase
{
    use RefreshDatabase;

    private Produk $produk;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        $this->produk = Produk::factory()->create();
    }

    public function test_foto_diperkecil_dan_dibuat_versi_kecil(): void
    {
        $this->post(route('admin.toko.foto.store', $this->produk), [
            'foto' => [UploadedFile::fake()->image('kopi.jpg', 3000, 2000)],
        ])->assertSessionHas('sukses');

        $foto = $this->produk->foto()->sole();
        Storage::disk('public')->assertExists([$foto->path, $foto->path_kecil]);

        [$lebar, $tinggi] = getimagesize(Storage::disk('public')->path($foto->path));
        $this->assertSame([1200, 800], [$lebar, $tinggi]);

        [$lebarKecil] = getimagesize(Storage::disk('public')->path($foto->path_kecil));
        $this->assertSame(400, $lebarKecil);
        $this->assertDatabaseHas('toko_aktivitas', ['aksi' => 'tambah_foto']);
    }

    public function test_beberapa_foto_sekaligus_berurutan(): void
    {
        $this->post(route('admin.toko.foto.store', $this->produk), [
            'foto' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
        ]);

        $this->assertSame([0, 1], $this->produk->foto()->pluck('urutan')->all());
    }

    public function test_maksimal_lima_foto(): void
    {
        $this->post(route('admin.toko.foto.store', $this->produk), [
            'foto' => array_map(fn ($i) => UploadedFile::fake()->image("f{$i}.jpg"), range(1, 4)),
        ]);

        $this->post(route('admin.toko.foto.store', $this->produk), [
            'foto' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertSessionHasErrors('foto');

        $this->assertSame(4, $this->produk->foto()->count());
    }

    public function test_berkas_bukan_gambar_ditolak(): void
    {
        $this->post(route('admin.toko.foto.store', $this->produk), [
            'foto' => [UploadedFile::fake()->create('daftar-harga.pdf', 10, 'application/pdf')],
        ])->assertSessionHasErrors('foto.0');

        $this->assertSame(0, $this->produk->foto()->count());
    }

    public function test_hapus_foto_menghapus_berkasnya(): void
    {
        $this->post(route('admin.toko.foto.store', $this->produk), ['foto' => [UploadedFile::fake()->image('a.jpg')]]);
        $foto = $this->produk->foto()->sole();

        $this->delete(route('admin.toko.foto.destroy', [$this->produk, $foto]))->assertSessionHas('sukses');

        Storage::disk('public')->assertMissing([$foto->path, $foto->path_kecil]);
        $this->assertModelMissing($foto);
    }

    public function test_geser_menukar_urutan_foto(): void
    {
        $this->post(route('admin.toko.foto.store', $this->produk), [
            'foto' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ]);
        [$pertama, $kedua] = $this->produk->foto()->get();

        $this->post(route('admin.toko.foto.geser', [$this->produk, $kedua, 'naik']));

        $this->assertSame([$kedua->id, $pertama->id], $this->produk->foto()->pluck('id')->all());
    }

    public function test_foto_produk_lain_tidak_bisa_diubah_lewat_produk_ini(): void
    {
        $lain = Produk::factory()->create();
        $foto = FotoProduk::query()->create(['produk_id' => $lain->id, 'path' => 'a.webp', 'path_kecil' => 'a-kecil.webp']);

        $this->delete(route('admin.toko.foto.destroy', [$this->produk, $foto]))->assertNotFound();
        $this->post(route('admin.toko.foto.geser', [$this->produk, $foto, 'naik']))->assertNotFound();
        $this->assertModelExists($foto);
    }
}
