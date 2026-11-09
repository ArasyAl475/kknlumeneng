<?php

namespace App\Services\Desain;

use Closure;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Menyimpan gambar tata letak unggahan admin. Berkas selalu ditulis ulang dengan nama acak:
 * ukurannya mengecil untuk sinyal lemah, dan data tersembunyi (lokasi GPS, kode sisipan) ikut terbuang.
 */
class PenyimpanGambarSitus
{
    public const MAKS_KB = 3072;

    public const FOLDER = 'desain';

    // Cukup tajam untuk foto hero di layar laptop lebar, tanpa membebani HP.
    private const SISI_TERPANJANG = 2000;

    // GD memakai sekitar 4 byte per piksel; batas ini mencegah server kehabisan memori.
    private const MAKS_PIKSEL = 30_000_000;

    private const MUTU = 80;

    /**
     * @return array<int, mixed>
     */
    public static function aturan(): array
    {
        return [
            'nullable',
            'file',
            'mimes:jpg,jpeg,png,webp',
            'mimetypes:image/jpeg,image/png,image/webp',
            'max:'.self::maksimalKb(),
            function (string $kolom, mixed $berkas, Closure $gagal): void {
                $ukuran = $berkas instanceof UploadedFile ? @getimagesize($berkas->getRealPath()) : false;

                if ($ukuran === false) {
                    $gagal('Berkas tidak dapat dibaca sebagai gambar. Coba pilih gambar lain.');
                } elseif ($ukuran[0] * $ukuran[1] > self::MAKS_PIKSEL) {
                    $gagal('Resolusi gambar terlalu besar. Pakai gambar di bawah 30 megapiksel.');
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
            "{$kolom}.file" => 'Gambar harus berupa berkas.',
            "{$kolom}.mimes" => 'Gambar harus berformat JPG, PNG, atau WebP.',
            "{$kolom}.mimetypes" => 'Gambar harus berformat JPG, PNG, atau WebP.',
            "{$kolom}.max" => "Ukuran gambar paling besar {$batas}.",
            "{$kolom}.uploaded" => "Gambar gagal diunggah. Pastikan ukurannya tidak lebih dari {$batas}, lalu coba lagi.",
        ];
    }

    // Batas server bisa lebih kecil dari batas aplikasi; bantuan di formulir harus jujur soal itu.
    public static function maksimalKb(): int
    {
        return (int) min(self::MAKS_KB, floor(UploadedFile::getMaxFilesize() / 1024));
    }

    public static function teksBatas(): string
    {
        return str_replace('.', ',', (string) round(self::maksimalKb() / 1024, 1)).' MB';
    }

    public function simpan(UploadedFile $berkas, string $kolom): string
    {
        $jalur = extension_loaded('gd')
            ? $this->tulisUlang($berkas, $kolom)
            : $berkas->storeAs(self::FOLDER, Str::random(40).'.'.$berkas->extension(), 'public');

        if (! $jalur) {
            throw ValidationException::withMessages([$kolom => 'Gambar gagal disimpan. Silakan coba lagi.']);
        }

        return $jalur;
    }

    /**
     * Hanya berkas di folder desain/ yang boleh dihapus. Gambar bawaan (public/images/default)
     * dan foto modul lain tidak pernah tersentuh, walau jalurnya entah bagaimana tersimpan di slot.
     */
    public function hapus(?string $jalur): void
    {
        if ($jalur && str_starts_with($jalur, self::FOLDER.'/') && ! str_contains($jalur, '..')) {
            Storage::disk('public')->delete($jalur);
        }
    }

    private function tulisUlang(UploadedFile $berkas, string $kolom): string|false
    {
        $asli = @imagecreatefromstring((string) file_get_contents($berkas->getRealPath()));

        if ($asli === false) {
            throw ValidationException::withMessages([$kolom => 'Berkas tidak dapat dibaca sebagai gambar. Coba pilih gambar lain.']);
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
