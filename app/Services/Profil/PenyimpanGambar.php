<?php

namespace App\Services\Profil;

use Closure;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Foto profil desa, perangkat, dan berita. Sengaja milik modul ini sendiri (bukan memakai
 * layanan Edukasi) agar perubahan di satu modul tidak diam-diam mengubah modul lain.
 */
class PenyimpanGambar
{
    public const MAKS_KB = 5120;

    // Cukup tajam untuk layar HP dan laptop, tetapi jauh lebih ringan daripada foto asli kamera.
    private const SISI_TERPANJANG = 1400;

    // GD memakai sekitar 4 byte per piksel; batas ini mencegah server kehabisan memori.
    private const MAKS_PIKSEL = 20_000_000;

    private const MUTU = 80;

    private const FOLDER = 'profil';

    /**
     * @return array<int, mixed>
     */
    public static function aturan(): array
    {
        return [
            'nullable',
            'file',
            'mimes:jpg,jpeg,png,webp',
            'max:'.self::maksimalKb(),
            function (string $kolom, mixed $berkas, Closure $gagal): void {
                $ukuran = $berkas instanceof UploadedFile ? @getimagesize($berkas->getRealPath()) : false;

                if ($ukuran === false) {
                    $gagal('Berkas tidak dapat dibaca sebagai gambar. Coba pilih foto lain.');
                } elseif ($ukuran[0] * $ukuran[1] > self::MAKS_PIKSEL) {
                    $gagal('Resolusi foto terlalu besar. Pakai foto di bawah 20 megapiksel.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function pesan(string $kolom): array
    {
        $batas = self::teksBatas();

        return [
            "{$kolom}.file" => 'Foto harus berupa berkas.',
            "{$kolom}.mimes" => 'Foto harus berformat JPG, PNG, atau WebP.',
            "{$kolom}.max" => "Ukuran foto paling besar {$batas}.",
            "{$kolom}.uploaded" => "Foto gagal diunggah. Pastikan ukurannya tidak lebih dari {$batas}, lalu coba lagi.",
        ];
    }

    // Batas server bisa lebih kecil dari batas aplikasi, dan bantuan di formulir harus jujur soal itu.
    public static function maksimalKb(): int
    {
        return (int) min(self::MAKS_KB, floor(UploadedFile::getMaxFilesize() / 1024));
    }

    public static function teksBatas(): string
    {
        return str_replace('.', ',', (string) round(self::maksimalKb() / 1024, 1)).' MB';
    }

    // Tidak memakai APP_URL, agar gambar tetap tampil saat situs dibuka lewat alamat lain.
    public static function url(?string $jalur): ?string
    {
        return $jalur ? asset('storage/'.$jalur) : null;
    }

    /**
     * Alamat tanpa nama situs (/storage/...), untuk foto di dalam isi berita. Isi itu disimpan
     * di basis data, jadi tidak boleh terikat pada alamat saat ditulis (localhost, tunnel, domain).
     */
    public static function urlRelatif(string $jalur): string
    {
        return parse_url(asset('storage/'.$jalur), PHP_URL_PATH) ?: '/storage/'.$jalur;
    }

    /**
     * Foto selalu ditulis ulang, bukan disalin. Selain mengecilkan ukuran, ini membuang data
     * tersembunyi seperti lokasi GPS dari kamera HP dan kode yang disisipkan ke dalam berkas.
     */
    public function simpan(UploadedFile $berkas, string $kolom): string
    {
        $jalur = extension_loaded('gd')
            ? $this->tulisUlang($berkas, $kolom)
            // Tanpa GD foto tetap tersimpan apa adanya, agar admin tidak terhalang mengisi profil.
            : $berkas->store(self::FOLDER, 'public');

        if (! $jalur) {
            throw ValidationException::withMessages([$kolom => 'Foto gagal disimpan. Silakan coba lagi.']);
        }

        return $jalur;
    }

    public function hapus(?string $jalur): void
    {
        if ($jalur) {
            Storage::disk('public')->delete($jalur);
        }
    }

    /**
     * Satu aturan ganti-foto untuk semua isian: foto baru menggantikan yang lama, centang
     * "hapus" mengosongkan, selain itu foto lama dipertahankan.
     */
    public function ganti(?string $lama, ?UploadedFile $baru, bool $hapus, string $kolom): ?string
    {
        if ($baru) {
            return $this->simpan($baru, $kolom);
        }

        return $hapus ? null : $lama;
    }

    private function tulisUlang(UploadedFile $berkas, string $kolom): string|false
    {
        $asli = @imagecreatefromstring((string) file_get_contents($berkas->getRealPath()));

        if ($asli === false) {
            throw ValidationException::withMessages([$kolom => 'Berkas tidak dapat dibaca sebagai gambar. Coba pilih foto lain.']);
        }

        [$biner, $ekstensi] = $this->sandikan($this->kecilkan($this->putarSesuaiKamera($asli, $berkas)));
        $jalur = self::FOLDER.'/'.Str::random(40).'.'.$ekstensi;

        return Storage::disk('public')->put($jalur, $biner) ? $jalur : false;
    }

    // Foto HP sering disimpan miring dengan catatan arah; catatan itu ikut hilang saat ditulis ulang.
    private function putarSesuaiKamera(GdImage $gambar, UploadedFile $berkas): GdImage
    {
        if (! function_exists('exif_read_data') || $berkas->getMimeType() !== 'image/jpeg') {
            return $gambar;
        }

        $sudut = match ((@exif_read_data($berkas->getRealPath()) ?: [])['Orientation'] ?? 1) {
            3 => 180,
            6 => 270,
            8 => 90,
            default => 0,
        };

        return $sudut === 0 ? $gambar : (imagerotate($gambar, $sudut, 0) ?: $gambar);
    }

    private function kecilkan(GdImage $asli): GdImage
    {
        $lebar = imagesx($asli);
        $tinggi = imagesy($asli);
        $skala = min(1, self::SISI_TERPANJANG / max($lebar, $tinggi));
        $lebarBaru = max(1, (int) round($lebar * $skala));
        $tinggiBaru = max(1, (int) round($tinggi * $skala));

        $hasil = imagecreatetruecolor($lebarBaru, $tinggiBaru);
        // Latar putih agar bagian transparan pada PNG tidak berubah hitam.
        imagefill($hasil, 0, 0, (int) imagecolorallocate($hasil, 255, 255, 255));
        imagecopyresampled($hasil, $asli, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebar, $tinggi);

        return $hasil;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function sandikan(GdImage $gambar): array
    {
        $bisaWebp = function_exists('imagewebp') && (imagetypes() & IMG_WEBP);

        ob_start();
        $bisaWebp ? imagewebp($gambar, null, self::MUTU) : imagejpeg($gambar, null, self::MUTU);

        return [(string) ob_get_clean(), $bisaWebp ? 'webp' : 'jpg'];
    }
}
