<?php

namespace Tests\Unit\Edukasi;

use App\Services\Edukasi\PengolahGambar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('gd')]
class PengolahGambarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function ukuranTersimpan(string $jalur): array
    {
        return array_slice(getimagesizefromstring(Storage::disk('public')->get($jalur)), 0, 2);
    }

    public function test_foto_besar_dikecilkan_dengan_perbandingan_tetap(): void
    {
        $jalur = (new PengolahGambar)->simpan(UploadedFile::fake()->image('foto.jpg', 2400, 1200));

        $this->assertSame([1200, 600], $this->ukuranTersimpan($jalur));
    }

    public function test_foto_tegak_dibatasi_pada_sisi_terpanjang(): void
    {
        $jalur = (new PengolahGambar)->simpan(UploadedFile::fake()->image('foto.jpg', 1000, 3000));

        $this->assertSame([400, 1200], $this->ukuranTersimpan($jalur));
    }

    public function test_foto_kecil_tidak_diperbesar(): void
    {
        $jalur = (new PengolahGambar)->simpan(UploadedFile::fake()->image('foto.png', 300, 200));

        $this->assertSame([300, 200], $this->ukuranTersimpan($jalur));
    }

    public function test_nama_berkas_acak_bukan_nama_kiriman(): void
    {
        $jalur = (new PengolahGambar)->simpan(UploadedFile::fake()->image('nama-warga.jpg', 10, 10));

        $this->assertStringStartsWith('edukasi/', $jalur);
        $this->assertStringNotContainsString('nama-warga', $jalur);
        $this->assertMatchesRegularExpression('/\.(webp|jpg)$/', $jalur);
    }

    public function test_berkas_rusak_ditolak_dengan_pesan_bukan_galat_server(): void
    {
        $this->expectException(ValidationException::class);

        (new PengolahGambar)->simpan(UploadedFile::fake()->createWithContent('rusak.jpg', 'bukan gambar'));
    }

    #[RequiresPhpExtension('exif')]
    public function test_foto_miring_dari_kamera_diputar_dan_data_kamera_dibuang(): void
    {
        $jalur = (new PengolahGambar)->simpan($this->jpegDenganOrientasi(100, 50, 6));

        $this->assertSame([50, 100], $this->ukuranTersimpan($jalur));
        $this->assertStringNotContainsString('Exif', Storage::disk('public')->get($jalur));
    }

    public function test_alamat_gambar_mengikuti_alamat_situs_yang_dibuka(): void
    {
        $this->assertNull(PengolahGambar::url(null));
        $this->assertSame(asset('storage/edukasi/a.webp'), PengolahGambar::url('edukasi/a.webp'));
    }

    // Menyisipkan catatan arah kamera ke JPEG, sama seperti yang dilakukan kamera HP.
    private function jpegDenganOrientasi(int $lebar, int $tinggi, int $orientasi): UploadedFile
    {
        $gambar = imagecreatetruecolor($lebar, $tinggi);
        ob_start();
        imagejpeg($gambar);
        $jpeg = (string) ob_get_clean();

        $ifd = "\x01\x00"."\x12\x01\x03\x00\x01\x00\x00\x00".pack('v', $orientasi)."\x00\x00"."\x00\x00\x00\x00";
        $exif = "Exif\0\0"."II*\0\x08\x00\x00\x00".$ifd;
        $segmen = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;

        return UploadedFile::fake()->createWithContent('foto.jpg', substr($jpeg, 0, 2).$segmen.substr($jpeg, 2));
    }
}
