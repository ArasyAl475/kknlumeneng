<?php

namespace App\Services\Toko;

use App\Http\Requests\Toko\PanenJamurRequest;
use App\Http\Requests\Toko\PanenKopiRequest;
use App\Http\Requests\Toko\PenjualanJamurRequest;
use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use App\Models\Toko\PenjualanJamur;
use DateTimeImmutable;
use DomainException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Menambah catatan hasil panen dari berkas Excel (templat polos, bukan berkas ekspor).
 *
 * Tiap baris diperiksa dengan aturan FormRequest yang sama seperti form admin, jadi data dari Excel
 * tidak pernah lebih longgar daripada data yang diketik di form. Sel berisi rumus ditolak (tidak
 * dijalankan). Hanya MENAMBAH catatan baru; tidak mengubah atau menghapus catatan lama.
 */
class ImporExcelHasil
{
    public const MAKS_BARIS = 500;

    private const NAMA_LEMBAR = 'Data';

    private const TEPI_FORMAT = self::MAKS_BARIS + 1;

    /**
     * Urutan kolom di sini = urutan kolom di templat.
     * tipe: teks | tanggal | desimal | bilangan | media
     */
    public const JENIS = [
        'panen-kopi' => [
            'label' => 'Panen kopi',
            'model' => PanenKopi::class,
            'request' => PanenKopiRequest::class,
            'kolom' => [
                'tanggal_panen' => ['judul' => 'Tanggal panen', 'tipe' => 'tanggal', 'contoh' => '05-10-2026'],
                'jenis_kopi' => ['judul' => 'Jenis kopi', 'tipe' => 'teks', 'contoh' => 'Robusta'],
                'tempat_panen' => ['judul' => 'Tempat panen', 'tipe' => 'teks', 'contoh' => 'Kebun Krajan'],
                'nama_petani' => ['judul' => 'Nama petani/kelompok', 'tipe' => 'teks', 'contoh' => 'KWT Giri', 'opsional' => true],
                'jumlah_kg' => ['judul' => 'Jumlah panen (kg)', 'tipe' => 'desimal', 'contoh' => '100,5'],
                'limbah_kg' => ['judul' => 'Jumlah limbah (kg)', 'tipe' => 'desimal', 'contoh' => '20,25'],
            ],
        ],
        'panen-jamur' => [
            'label' => 'Panen jamur',
            'model' => PanenJamur::class,
            'request' => PanenJamurRequest::class,
            'kolom' => [
                'tanggal_panen' => ['judul' => 'Tanggal panen', 'tipe' => 'tanggal', 'contoh' => '05-10-2026'],
                'dengan_cangkang_kopi' => ['judul' => 'Media tanam', 'tipe' => 'media', 'contoh' => 'Dengan cangkang kopi'],
                'jumlah_baglog' => ['judul' => 'Jumlah baglog', 'tipe' => 'bilangan', 'contoh' => '40'],
                'jumlah_kg' => ['judul' => 'Jumlah panen (kg)', 'tipe' => 'desimal', 'contoh' => '12,5'],
                'biaya_baglog_rupiah' => ['judul' => 'Biaya baglog (Rp)', 'tipe' => 'bilangan', 'contoh' => '300000', 'opsional' => true],
            ],
        ],
        'penjualan-jamur' => [
            'label' => 'Penjualan jamur',
            'model' => PenjualanJamur::class,
            'request' => PenjualanJamurRequest::class,
            'kolom' => [
                'tanggal_penjualan' => ['judul' => 'Tanggal penjualan', 'tipe' => 'tanggal', 'contoh' => '06-10-2026'],
                'dengan_cangkang_kopi' => ['judul' => 'Media tanam', 'tipe' => 'media', 'contoh' => 'Tanpa cangkang kopi'],
                'jumlah_kg' => ['judul' => 'Jumlah terjual (kg)', 'tipe' => 'desimal', 'contoh' => '8'],
                'total_rupiah' => ['judul' => 'Uang diterima (Rp)', 'tipe' => 'bilangan', 'contoh' => '240000'],
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
        return self::JENIS[$kunci] ?? throw new InvalidArgumentException("Jenis impor tidak dikenal: {$kunci}");
    }

    public function namaTemplat(string $kunci): string
    {
        return "templat-impor-{$kunci}.xlsx";
    }

    // ───────────────────────────── Templat ─────────────────────────────

    public function templat(string $kunci): Spreadsheet
    {
        $def = $this->definisi($kunci);
        $buku = new Spreadsheet;
        $buku->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
        $buku->getProperties()->setCreator('Web Desa Lumeneng')->setTitle('Templat impor '.Str::lower($def['label']));

        $ws = $buku->getActiveSheet();
        $ws->setTitle(self::NAMA_LEMBAR);
        $akhir = Coordinate::stringFromColumnIndex(count($def['kolom']));

        $i = 1;
        foreach ($def['kolom'] as $k) {
            $huruf = Coordinate::stringFromColumnIndex($i++);
            $ws->setCellValueExplicit($huruf.'1', $k['judul'], DataType::TYPE_STRING);
            $ws->getColumnDimension($huruf)->setWidth(max(strlen($k['judul']) + 6, 20));
            $format = match ($k['tipe']) {
                'tanggal' => 'dd-mm-yyyy',
                'desimal' => '#,##0.00',
                'bilangan' => '#,##0',
                default => null,
            };
            if ($format) {
                $ws->getStyle("{$huruf}2:{$huruf}".self::TEPI_FORMAT)->getNumberFormat()->setFormatCode($format);
            }
            if ($k['tipe'] === 'media') {
                $ws->setDataValidation("{$huruf}2:{$huruf}".self::TEPI_FORMAT, $this->pilihanMedia());
            }
        }

        $kepala = $ws->getStyle("A1:{$akhir}1");
        $kepala->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $kepala->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F5A3A');
        $kepala->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $ws->getRowDimension(1)->setRowHeight(30);
        $ws->freezePane('A2');

        $this->lembarPetunjuk($buku->createSheet(), $def);
        $buku->setActiveSheetIndex(0);

        return $buku;
    }

    private function pilihanMedia(): DataValidation
    {
        $pilihan = implode(',', RekapHasil::MEDIA);

        return (new DataValidation)
            ->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowDropDown(true)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Pilihan tidak dikenal')
            ->setError('Pilih salah satu dari daftar.')
            ->setFormula1('"'.$pilihan.'"');
    }

    private function lembarPetunjuk(Worksheet $ws, array $def): void
    {
        $ws->setTitle('Petunjuk');
        $ws->getColumnDimension('A')->setWidth(28);
        $ws->getColumnDimension('B')->setWidth(18);
        $ws->getColumnDimension('C')->setWidth(60);

        $ws->setCellValueExplicit('A1', 'Cara mengisi templat '.Str::lower($def['label']), DataType::TYPE_STRING);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1F5A3A');

        $teks = [
            'Isi data di lembar "'.self::NAMA_LEMBAR.'", satu catatan per baris, mulai baris ke-2.',
            'Jangan mengubah, menghapus, atau memindah baris judul (baris ke-1).',
            'Paling banyak '.self::MAKS_BARIS.' baris sekali unggah.',
            'Tanggal ditulis hari-bulan-tahun, misalnya 05-10-2026. Tanggal tidak boleh melewati hari ini.',
            'Angka desimal boleh memakai koma (100,5) dan paling banyak 2 angka di belakang koma.',
            'Jangan memakai rumus; ketik angkanya langsung.',
            'Berkas ini hanya MENAMBAH catatan baru. Mengunggah berkas yang sama dua kali akan membuat catatan ganda.',
        ];
        $r = 3;
        foreach ($teks as $baris) {
            $ws->setCellValueExplicit("A{$r}", '• '.$baris, DataType::TYPE_STRING);
            $r++;
        }

        $r++;
        $ws->setCellValueExplicit("A{$r}", 'Kolom', DataType::TYPE_STRING);
        $ws->setCellValueExplicit("B{$r}", 'Wajib diisi?', DataType::TYPE_STRING);
        $ws->setCellValueExplicit("C{$r}", 'Contoh isian', DataType::TYPE_STRING);
        $ws->getStyle("A{$r}:C{$r}")->getFont()->setBold(true);
        foreach ($def['kolom'] as $k) {
            $r++;
            $ws->setCellValueExplicit("A{$r}", $k['judul'], DataType::TYPE_STRING);
            $ws->setCellValueExplicit("B{$r}", empty($k['opsional']) ? 'Ya' : 'Boleh kosong', DataType::TYPE_STRING);
            $ws->setCellValueExplicit("C{$r}", $k['contoh'], DataType::TYPE_STRING);
        }
    }

    // ───────────────────────────── Membaca ─────────────────────────────

    /**
     * @return array{baris: array<int, array{no: int, data: ?array, tampil: array<int, string>, galat: array<int, string>}>, jumlah_valid: int, jumlah_galat: int}
     *
     * @throws DomainException berkas tidak bisa dipakai (bukan templat, kosong, terlalu besar); pesannya ramah dibaca admin
     */
    public function baca(string $kunci, string $jalur): array
    {
        $def = $this->definisi($kunci);
        $ws = $this->bukaLembar($jalur);
        $peta = $this->petakanKepala($ws, $def);

        $terakhir = $ws->getHighestDataRow();
        if ($terakhir > self::MAKS_BARIS * 4) {
            throw new DomainException('Berkas punya terlalu banyak baris. Paling banyak '.self::MAKS_BARIS.' baris sekali unggah.');
        }

        $baris = [];
        for ($r = 2; $r <= $terakhir; $r++) {
            [$mentah, $galatSel] = $this->bacaBaris($ws, $peta, $r);
            if (collect($mentah)->every(fn ($n) => $n === null || $n === '') && $galatSel === []) {
                continue;
            }
            if (count($baris) >= self::MAKS_BARIS) {
                throw new DomainException('Berkas berisi lebih dari '.self::MAKS_BARIS.' baris data. Pecah menjadi beberapa berkas.');
            }
            $baris[] = $this->periksaBaris($def, $r, $mentah, $galatSel);
        }

        if ($baris === []) {
            throw new DomainException('Berkas tidak berisi baris data. Isi data mulai baris ke-2 lembar "'.self::NAMA_LEMBAR.'".');
        }

        $galat = count(array_filter($baris, fn (array $b) => $b['galat'] !== []));

        return ['baris' => $baris, 'jumlah_valid' => count($baris) - $galat, 'jumlah_galat' => $galat];
    }

    /**
     * Menyimpan baris yang SUDAH lolos pemeriksaan baca(). Semua atau tidak sama sekali.
     *
     * @param  array<int, array<string, mixed>>  $data
     */
    public function simpan(string $kunci, array $data): int
    {
        $model = $this->definisi($kunci)['model'];

        DB::transaction(function () use ($model, $data) {
            foreach ($data as $isi) {
                $model::query()->create($isi);
            }
        });

        return count($data);
    }

    private function bukaLembar(string $jalur): Worksheet
    {
        try {
            return IOFactory::createReader('Xlsx')->load($jalur)->getSheet(0);
        } catch (Throwable) {
            // Berkas rusak/bukan xlsx bisa memicu bermacam galat dari pustaka; admin cukup diberi satu pesan jelas.
            throw new DomainException('Berkas tidak bisa dibuka. Pastikan berkas Excel (.xlsx) dari templat dan tidak rusak.');
        }
    }

    /**
     * @return array<string, string> field => huruf kolom
     */
    private function petakanKepala(Worksheet $ws, array $def): array
    {
        $dikenal = [];
        $jumlahKolom = min(Coordinate::columnIndexFromString($ws->getHighestDataColumn()), 50);
        for ($c = 1; $c <= $jumlahKolom; $c++) {
            $huruf = Coordinate::stringFromColumnIndex($c);
            $judul = $this->rapikan($ws->getCell($huruf.'1')->getValue());
            if ($judul !== '') {
                $dikenal[$judul] ??= $huruf;
            }
        }

        $peta = [];
        $hilang = [];
        foreach ($def['kolom'] as $field => $k) {
            $huruf = $dikenal[$this->rapikan($k['judul'])] ?? null;
            $huruf ? $peta[$field] = $huruf : $hilang[] = $k['judul'];
        }

        if ($hilang !== []) {
            throw new DomainException('Berkas ini bukan templat '.Str::lower($def['label']).'. Kolom yang tidak ditemukan: '.implode(', ', $hilang).'. Unduh templatnya lagi lalu isi ulang.');
        }

        return $peta;
    }

    private function rapikan(mixed $nilai): string
    {
        return Str::lower(Str::squish((string) $nilai));
    }

    /**
     * @param  array<string, string>  $peta
     * @return array{0: array<string, mixed>, 1: array<string, string>} nilai mentah per field, galat per field
     */
    private function bacaBaris(Worksheet $ws, array $peta, int $baris): array
    {
        $mentah = [];
        $galat = [];
        foreach ($peta as $field => $huruf) {
            $sel = $ws->getCell($huruf.$baris);
            if ($sel->isFormula()) {
                $galat[$field] = 'Sel '.$huruf.$baris.' berisi rumus. Ketik angkanya langsung, atau salin lalu tempel sebagai nilai.';
                $mentah[$field] = null;

                continue;
            }
            $mentah[$field] = $sel->getValue();
        }

        return [$mentah, $galat];
    }

    /**
     * @param  array<string, mixed>  $mentah
     * @param  array<string, string>  $galatSel
     * @return array{no: int, data: ?array, tampil: array<int, string>, galat: array<int, string>}
     */
    private function periksaBaris(array $def, int $nomor, array $mentah, array $galatSel): array
    {
        [$data, $galat] = $this->periksaIsian($def, $mentah);
        $galat = $galatSel + $galat;

        return [
            'no' => $nomor,
            'data' => $galat === [] ? $data : null,
            'tampil' => $galat === [] ? $this->tampil($def, $data) : [],
            'galat' => array_values($galat),
        ];
    }

    /**
     * Mengubah isian mentah (dari Excel atau Google Sheets) lalu memeriksanya dengan aturan form admin.
     * Dipakai bersama oleh impor Excel dan sinkron Google Sheets supaya aturannya hanya satu.
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
            // Excel menyimpan tanggal sebagai nomor urut hari.
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function tampil(array $def, array $data): array
    {
        return array_values(array_map(function (string $field) use ($def, $data): string {
            $nilai = $data[$field] ?? null;
            if ($nilai === null || $nilai === '') {
                return '–';
            }

            return match ($def['kolom'][$field]['tipe']) {
                'tanggal' => FormatHasil::tanggal((string) $nilai),
                'desimal' => FormatHasil::angka((string) $nilai),
                'bilangan' => number_format((int) $nilai, 0, ',', '.'),
                'media' => RekapHasil::MEDIA[(int) $nilai],
                default => (string) $nilai,
            };
        }, array_keys($def['kolom'])));
    }
}
