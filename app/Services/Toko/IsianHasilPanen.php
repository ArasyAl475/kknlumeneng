<?php

namespace App\Services\Toko;

use App\Http\Requests\Toko\PanenJamurRequest;
use App\Http\Requests\Toko\PanenKopiRequest;
use App\Http\Requests\Toko\PenjualanJamurRequest;
use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use App\Models\Toko\PenjualanJamur;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Daftar kolom tiap jenis catatan hasil panen beserta pemeriksa isiannya, untuk isian yang diketik
 * di luar form admin (Google Sheets).
 *
 * Tiap isian diperiksa dengan aturan FormRequest yang sama seperti form admin, jadi data dari Sheet
 * tidak pernah lebih longgar daripada data yang diketik di form.
 */
class IsianHasilPanen
{
    /**
     * Urutan kolom di sini = urutan kolom di tab Google Sheets.
     * tipe: teks | tanggal | desimal | bilangan | media
     */
    public const JENIS = [
        'panen-kopi' => [
            'label' => 'Panen kopi',
            'model' => PanenKopi::class,
            'request' => PanenKopiRequest::class,
            'kolom' => [
                'tanggal_panen' => ['judul' => 'Tanggal panen', 'tipe' => 'tanggal'],
                'jenis_kopi' => ['judul' => 'Jenis kopi', 'tipe' => 'teks'],
                'tempat_panen' => ['judul' => 'Tempat panen', 'tipe' => 'teks'],
                'nama_petani' => ['judul' => 'Nama petani/kelompok', 'tipe' => 'teks'],
                'jumlah_kg' => ['judul' => 'Jumlah panen (kg)', 'tipe' => 'desimal'],
                'limbah_kg' => ['judul' => 'Jumlah limbah (kg)', 'tipe' => 'desimal'],
            ],
        ],
        'panen-jamur' => [
            'label' => 'Panen jamur',
            'model' => PanenJamur::class,
            'request' => PanenJamurRequest::class,
            'kolom' => [
                'tanggal_panen' => ['judul' => 'Tanggal panen', 'tipe' => 'tanggal'],
                'dengan_cangkang_kopi' => ['judul' => 'Media tanam', 'tipe' => 'media'],
                'jumlah_baglog' => ['judul' => 'Jumlah baglog', 'tipe' => 'bilangan'],
                'jumlah_kg' => ['judul' => 'Jumlah panen (kg)', 'tipe' => 'desimal'],
                'biaya_baglog_rupiah' => ['judul' => 'Biaya baglog (Rp)', 'tipe' => 'bilangan'],
            ],
        ],
        'penjualan-jamur' => [
            'label' => 'Penjualan jamur',
            'model' => PenjualanJamur::class,
            'request' => PenjualanJamurRequest::class,
            'kolom' => [
                'tanggal_penjualan' => ['judul' => 'Tanggal penjualan', 'tipe' => 'tanggal'],
                'dengan_cangkang_kopi' => ['judul' => 'Media tanam', 'tipe' => 'media'],
                'jumlah_kg' => ['judul' => 'Jumlah terjual (kg)', 'tipe' => 'desimal'],
                'total_rupiah' => ['judul' => 'Uang diterima (Rp)', 'tipe' => 'bilangan'],
            ],
        ],
    ];

    private const PESAN_MEDIA = 'Media tanam harus ditulis "Dengan cangkang kopi" atau "Tanpa cangkang kopi".';

    /**
     * @return array<int, string>
     */
    public static function kunci(): array
    {
        return array_keys(self::JENIS);
    }

    public function definisi(string $kunci): array
    {
        return self::JENIS[$kunci] ?? throw new InvalidArgumentException("Jenis catatan tidak dikenal: {$kunci}");
    }

    /**
     * Mengubah isian mentah (nilai sel Google Sheets) lalu memeriksanya dengan aturan form admin.
     *
     * @param  array<string, mixed>  $mentah  nilai sel per field
     * @return array{0: ?array, 1: array<string, string>} data bersih (null bila ada galat), pesan galat per field
     */
    public function periksaIsian(array $def, array $mentah): array
    {
        $isian = [];
        foreach ($def['kolom'] as $field => $k) {
            $isian[$field] = $this->ubah($k['tipe'], $mentah[$field] ?? null);
        }

        [$data, $galat] = $this->validasi($def['request'], $isian);

        foreach ($def['kolom'] as $field => $k) {
            if ($k['tipe'] === 'media' && isset($galat[$field]) && $isian[$field] !== null) {
                $galat[$field] = self::PESAN_MEDIA;
            }
        }

        return [$galat === [] ? $data : null, $galat];
    }

    /**
     * Memakai FormRequest yang sama dengan form admin, termasuk perapiannya (prepareForValidation).
     *
     * @param  class-string<FormRequest>  $kelas
     * @param  array<string, ?string>  $isian
     * @return array{0: ?array, 1: array<string, string>}
     */
    private function validasi(string $kelas, array $isian): array
    {
        $permintaan = $kelas::create('/', 'POST', $isian);
        $permintaan->setContainer(app())->setRedirector(app('redirect'));

        try {
            $permintaan->validateResolved();
        } catch (ValidationException $e) {
            return [null, array_map(fn (array $pesan) => $pesan[0], $e->errors())];
        }

        return [$permintaan->validated(), []];
    }

    // ───────────────────────────── Mengubah isi sel ─────────────────────────────

    private function ubah(string $tipe, mixed $nilai): ?string
    {
        if (is_object($nilai)) {
            $nilai = (string) $nilai;
        }
        if (is_string($nilai)) {
            $nilai = trim($nilai);
        }
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return match ($tipe) {
            'tanggal' => $this->ubahTanggal($nilai),
            'desimal' => $this->ubahDesimal($nilai),
            'bilangan' => $this->ubahBilangan($nilai),
            'media' => $this->ubahMedia((string) $nilai),
            default => (string) $nilai,
        };
    }

    private function ubahTanggal(mixed $nilai): string
    {
        if (is_int($nilai) || is_float($nilai)) {
            // Sheets (seperti Excel) menyimpan tanggal sebagai nomor urut hari bila sel diformat tanggal.
            return $nilai >= 1 && $nilai < 2958466 ? Date::excelToDateTimeObject($nilai)->format('Y-m-d') : (string) $nilai;
        }

        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d'] as $format) {
            $tanggal = DateTimeImmutable::createFromFormat('!'.$format, (string) $nilai);
            $galat = DateTimeImmutable::getLastErrors();
            if ($tanggal && ! ($galat && ($galat['warning_count'] || $galat['error_count']))) {
                return $tanggal->format('Y-m-d');
            }
        }

        return (string) $nilai;
    }

    private function ubahDesimal(mixed $nilai): string
    {
        if (is_int($nilai) || is_float($nilai)) {
            // Empat desimal cukup untuk membuang sisa hitungan komputer (0,1+0,2) tanpa menyembunyikan isian 3 desimal.
            return rtrim(rtrim(number_format((float) $nilai, 4, '.', ''), '0'), '.') ?: '0';
        }

        $teks = str_replace([' ', "\u{00A0}"], '', (string) $nilai);

        // Kebiasaan Indonesia: titik = pemisah ribuan, koma = desimal ("1.234,5").
        return str_contains($teks, ',') ? str_replace(',', '.', str_replace('.', '', $teks)) : $teks;
    }

    private function ubahBilangan(mixed $nilai): string
    {
        if (is_int($nilai) || is_float($nilai)) {
            return floor((float) $nilai) === (float) $nilai ? (string) (int) $nilai : (string) $nilai;
        }

        $teks = (string) preg_replace('/^rp|[\s.\x{00A0}]/iu', '', (string) $nilai);

        return ctype_digit($teks) ? $teks : (string) $nilai;
    }

    private function ubahMedia(string $nilai): string
    {
        $cocok = array_search($this->rapikan($nilai), array_map($this->rapikan(...), RekapHasil::MEDIA), true);

        return $cocok === false ? $nilai : (string) $cocok;
    }

    private function rapikan(mixed $nilai): string
    {
        return Str::lower(Str::squish((string) $nilai));
    }
}
