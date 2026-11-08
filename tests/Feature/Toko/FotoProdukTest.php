<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\FotoProduk;
use App\Models\Toko\Produk;
use App\Models\User;
use App\Services\Toko\PenyimpanFoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_foto_gif_dan_bmp_diterima_lalu_diubah_ke_format_web(): void
    {
        $this->post(route('admin.toko.foto.store', $this->produk), [
            'foto' => [UploadedFile::fake()->image('a.gif', 800, 600), UploadedFile::fake()->image('b.bmp', 800, 600)],
        ])->assertSessionHasNoErrors()->assertSessionHas('sukses');

        $tujuan = function_exists('imagewebp') ? 'image/webp' : 'image/jpeg';
        foreach ($this->produk->foto()->get() as $foto) {
            $this->assertSame($tujuan, getimagesize(Storage::disk('public')->path($foto->path))['mime']);
        }
        $this->assertSame(2, $this->produk->foto()->count());
    }

    /**
     * Kamera, WhatsApp, dan Windows memberi nama berbeda untuk isi yang sama (JPEG bisa .jpe atau .jfif,
     * kamera sering menulis huruf besar). Server menilai dari isi berkas, bukan dari nama ekstensinya.
     */
    public function test_semua_variasi_ekstensi_foto_umum_diterima(): void
    {
        $nama = [
            'a.jpg' => 'jpeg', 'b.jpeg' => 'jpeg', 'c.jpe' => 'jpeg', 'd.jfif' => 'jpeg', 'IMG_0001.JPG' => 'jpeg',
            'f.pjpeg' => 'jpeg', 'g.pjp' => 'jpeg', 'SCREENSHOT.PNG' => 'png', 'i.WEBP' => 'webp', 'j.Gif' => 'gif',
            'k.bmp' => 'bmp', 'l.dib' => 'bmp',
        ];

        // Dikirim lima-lima karena satu produk maksimal lima foto.
        foreach (array_chunk($nama, 5, true) as $kelompok) {
            $berkas = array_map(fn (string $jenis, string $namaBerkas) => $this->berkasAsli($namaBerkas, $jenis), $kelompok, array_keys($kelompok));

            $this->post(route('admin.toko.foto.store', $this->produk), ['foto' => $berkas])->assertSessionHasNoErrors();
            $this->assertSame(count($kelompok), $this->produk->foto()->count(), 'Ditolak: '.implode(', ', array_keys($kelompok)));

            $this->produk->foto()->get()->each(fn (FotoProduk $f) => PenyimpanFoto::hapus($f));
        }
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function kodeGagalUnggah(): array
    {
        return [
            'terlalu besar' => [UPLOAD_ERR_INI_SIZE, 'lebih besar dari batas server'],
            'terkirim sebagian' => [UPLOAD_ERR_PARTIAL, 'sinyal putus'],
            'folder sementara tidak ada' => [UPLOAD_ERR_NO_TMP_DIR, 'tidak bisa menyimpannya sementara (kode 6)'],
            'tidak bisa menulis' => [UPLOAD_ERR_CANT_WRITE, 'tidak bisa menyimpannya sementara (kode 7)'],
        ];
    }

    #[DataProvider('kodeGagalUnggah')]
    public function test_pesan_gagal_unggah_menyebut_penyebab_sebenarnya(int $kode, string $potongan): void
    {
        $path = tempnam(sys_get_temp_dir(), 'foto');
        file_put_contents($path, 'x');
        $berkas = new UploadedFile($path, 'baglog.jpe', 'image/jpeg', $kode, true);

        $this->post(route('admin.toko.foto.store', $this->produk), ['foto' => [$berkas]])
            ->assertSessionHasErrors('foto.0');

        $pesan = session('errors')->first('foto.0');
        $this->assertStringContainsString($potongan, $pesan);
        $this->assertStringContainsString('"baglog.jpe"', $pesan);
    }

    public function test_isi_berkas_yang_menentukan_bukan_nama_ekstensinya(): void
    {
        // PDF yang dinamai .jpg tetap ditolak: nama ekstensi bisa diganti siapa saja.
        $palsu = $this->berkasAsli('bukan-foto.jpg', null, "%PDF-1.4\n%".str_repeat('x', 100));

        $this->post(route('admin.toko.foto.store', $this->produk), ['foto' => [$palsu]])
            ->assertSessionHasErrors('foto.0');
        $this->assertSame(0, $this->produk->foto()->count());
    }

    /**
     * Berkas sungguhan di disk, sehingga jenisnya dibaca dari isi seperti unggahan nyata.
     * UploadedFile::fake() menebak jenis dari nama berkas, jadi tidak bisa menguji variasi ekstensi.
     */
    private function berkasAsli(string $nama, ?string $jenis, ?string $isi = null): UploadedFile
    {
        if ($isi === null) {
            $gambar = imagecreatetruecolor(60, 40);
            ob_start();
            match ($jenis) {
                'jpeg' => imagejpeg($gambar),
                'png' => imagepng($gambar),
                'webp' => imagewebp($gambar),
                'gif' => imagegif($gambar),
                'bmp' => imagebmp($gambar),
            };
            $isi = (string) ob_get_clean();
        }

        $path = tempnam(sys_get_temp_dir(), 'foto');
        file_put_contents($path, $isi);

        return new UploadedFile($path, $nama, null, null, true);
    }

    public function test_foto_avif_diterima_bila_gd_server_mendukung(): void
    {
        if (! function_exists('imageavif') || ! function_exists('imagecreatefromavif')) {
            $this->markTestSkipped('GD di mesin ini tidak mendukung AVIF.');
        }

        $gambar = imagecreatetruecolor(300, 200);
        ob_start();
        imageavif($gambar);
        $isi = (string) ob_get_clean();

        $this->post(route('admin.toko.foto.store', $this->produk), [
            'foto' => [UploadedFile::fake()->createWithContent('a.avif', $isi)],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $this->produk->foto()->count());
    }

    public function test_foto_heic_ditolak_dengan_petunjuk_untuk_iphone(): void
    {
        // Kepala berkas HEIC minimal: cukup untuk dikenali sebagai HEIC, tidak bisa dibaca GD.
        $heic = "\x00\x00\x00\x18ftypheic\x00\x00\x00\x00mif1heic".str_repeat("\x00", 64);

        $this->post(route('admin.toko.foto.store', $this->produk), [
            'foto' => [UploadedFile::fake()->createWithContent('IMG_0001.heic', $heic)],
        ])->assertSessionHasErrors('foto.0');

        $this->assertStringContainsString('HEIC', session('errors')->first('foto.0'));
        $this->assertSame(0, $this->produk->foto()->count());
    }

    public function test_form_foto_memuat_penanda_pengecil_otomatis(): void
    {
        $this->get(route('admin.toko.produk.edit', $this->produk))->assertOk()
            ->assertSee('data-unggah-foto', false)
            ->assertSee('data-status-foto', false)
            ->assertSee('diperkecil dan diubah ke JPG')
            ->assertSee('.jpe,.jfif,.pjpeg,.pjp', false)
            ->assertSee('JPG (.jpg, .jpeg, .jpe, .jfif)')
            ->assertDontSee('heic', false);
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
