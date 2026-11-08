<?php

namespace App\Services\Toko;

use App\Models\Toko\FotoProduk;
use App\Models\Toko\Produk;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Foto HP bisa 3–6 MB; tanpa diperkecil, halaman produk tidak akan terbuka di sinyal lemah.
 * Memakai GD bawaan PHP agar tidak perlu paket tambahan.
 */
final class PenyimpanFoto
{
    public const SISI_BESAR = 1200;

    public const SISI_KECIL = 400;

    private const MUTU = 80;

    public static function simpan(Produk $produk, UploadedFile $berkas, int $urutan): FotoProduk
    {
        $asli = self::baca($berkas);
        $folder = "toko/produk/{$produk->id}";
        $nama = Str::random(24);
        $ekstensi = function_exists('imagewebp') ? 'webp' : 'jpg';

        $path = "{$folder}/{$nama}.{$ekstensi}";
        $pathKecil = "{$folder}/{$nama}-kecil.{$ekstensi}";

        $disk = Storage::disk('public');
        $disk->put($path, self::kodekan(self::perkecil($asli, self::SISI_BESAR), $ekstensi));
        $disk->put($pathKecil, self::kodekan(self::perkecil($asli, self::SISI_KECIL), $ekstensi));

        return $produk->foto()->create(['path' => $path, 'path_kecil' => $pathKecil, 'urutan' => $urutan]);
    }

    public static function hapus(FotoProduk $foto): void
    {
        Storage::disk('public')->delete([$foto->path, $foto->path_kecil]);
        $foto->delete();
    }

    private static function baca(UploadedFile $berkas): GdImage
    {
        $isi = file_get_contents($berkas->getRealPath());
        $gambar = $isi === false ? false : @imagecreatefromstring($isi);

        if (! $gambar instanceof GdImage) {
            throw new RuntimeException('Berkas bukan gambar yang bisa dibaca.');
        }

        return self::luruskan($gambar, $berkas);
    }

    // Kamera HP menyimpan foto miring dan hanya menandainya di EXIF; tanpa ini foto tampil terbalik.
    private static function luruskan(GdImage $gambar, UploadedFile $berkas): GdImage
    {
        if (! function_exists('exif_read_data') || ! in_array($berkas->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            return $gambar;
        }

        $orientasi = (int) (@exif_read_data($berkas->getRealPath())['Orientation'] ?? 1);
        $sudut = [3 => 180, 6 => -90, 8 => 90][$orientasi] ?? 0;

        if ($sudut === 0) {
            return $gambar;
        }

        $diputar = imagerotate($gambar, $sudut, 0);

        return $diputar instanceof GdImage ? $diputar : $gambar;
    }

    private static function perkecil(GdImage $asli, int $sisiMaks): GdImage
    {
        $lebar = imagesx($asli);
        $tinggi = imagesy($asli);
        $skala = min(1, $sisiMaks / max($lebar, $tinggi));
        $lebarBaru = max(1, (int) round($lebar * $skala));
        $tinggiBaru = max(1, (int) round($tinggi * $skala));

        $hasil = imagecreatetruecolor($lebarBaru, $tinggiBaru);
        // Latar putih supaya PNG transparan tidak berubah hitam.
        imagefill($hasil, 0, 0, imagecolorallocate($hasil, 255, 255, 255));
        imagecopyresampled($hasil, $asli, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebar, $tinggi);

        return $hasil;
    }

    private static function kodekan(GdImage $gambar, string $ekstensi): string
    {
        ob_start();
        $ekstensi === 'webp' ? imagewebp($gambar, null, self::MUTU) : imagejpeg($gambar, null, self::MUTU);

        return (string) ob_get_clean();
    }
}
