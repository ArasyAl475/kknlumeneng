<?php

namespace App\Services\Toko;

use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Membuat berkas Excel dari data yang sama dengan halaman Hasil Panen (filter yang sama),
 * sehingga angka di Excel selalu cocok dengan yang dilihat warga di layar.
 *
 * Aturan tampilan: tiap lembar punya judul, keterangan, tabel berkepala hijau, baris selang-seling,
 * dan baris jumlah. Semua nilai ditulis sebagai angka/teks tetap (bukan rumus) agar terbaca juga
 * di aplikasi pratinjau HP yang tidak menghitung rumus. Data dari pengguna selalu ditulis sebagai
 * teks, jadi isian seperti "=1+1" tidak pernah dijalankan sebagai rumus.
 *
 * Sengaja tanpa harga per transaksi dan biaya baglog: itu khusus admin (lihat RekapHasil::keuanganJamur).
 */
class EksporExcelHasil
{
    private const HIJAU_TUA = '1F5A3A';

    private const HIJAU_MUDA = 'E8F3EC';

    private const GARIS = 'C9D6CD';

    private const REDUP = '5B6B60';

    private const FORMAT_KG = '#,##0.00';

    private const FORMAT_RUPIAH = '"Rp" #,##0';

    private const FORMAT_PERSEN = '0.0%';

    private const FORMAT_TANGGAL = 'dd-mm-yyyy';

    private const FORMAT_BILANGAN = '#,##0';

    public function __construct(private RekapHasil $rekap, private AnalisisHasil $analisis) {}

    public function namaBerkas(): string
    {
        return 'hasil-panen-desa-lumeneng-'.now()->format('Y-m-d').'.xlsx';
    }

    /**
     * @param  array{dari?: ?string, sampai?: ?string, jenis?: ?string, tempat?: ?string}  $filter
     */
    public function buat(array $filter = []): Spreadsheet
    {
        $buku = new Spreadsheet;
        $buku->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
        $buku->getProperties()
            ->setCreator('Web Desa Lumeneng')
            ->setTitle('Hasil Panen dan Penjualan Komoditas Desa Lumeneng')
            ->setSubject('Rekap hasil panen kopi, panen jamur, dan penjualan jamur');

        $ket = $this->keteranganFilter($filter);
        $kopi = $this->rekap->kopi($filter);
        $jamur = $this->rekap->jamur($filter);
        $penjualan = $this->rekap->penjualan($filter);
        $perbandingan = $this->rekap->perbandinganJamur($filter);

        $this->lembarRingkasan($buku->getActiveSheet(), $ket, $kopi, $jamur, $penjualan);
        $this->lembarPerkembangan($buku->createSheet(), $ket, $this->analisis->deret($filter));
        $this->lembarPanenKopi($buku->createSheet(), $ket, $this->rekap->daftarKopi($filter));
        $this->lembarRekapKopi($buku->createSheet(), $ket, $kopi);
        $this->lembarPanenJamur($buku->createSheet(), $ket, $this->rekap->daftarPanenJamur($filter));
        $this->lembarPenjualanJamur($buku->createSheet(), $ket, $this->rekap->daftarPenjualanJamur($filter));
        $this->lembarBaglog($buku->createSheet(), $ket, $perbandingan, $this->analisis->kesimpulanBaglog($perbandingan));

        $buku->setActiveSheetIndex(0);

        return $buku;
    }

    // ───────────────────────────── Lembar ─────────────────────────────

    private function lembarRingkasan(Worksheet $ws, array $ket, array $kopi, array $jamur, array $penjualan): void
    {
        $this->kepala($ws, 'Ringkasan', 'Hasil Panen dan Penjualan Komoditas Desa', 'Desa Lumeneng, Kec. Paninggaran, Kab. Pekalongan', 4);

        $r = 4;
        $r = $this->bagian($ws, $r, 'Data yang ditampilkan', 4);
        foreach ([
            'Rentang waktu' => $ket['waktu'],
            'Jenis kopi' => $ket['jenis'],
            'Tempat panen' => $ket['tempat'],
            'Diunduh pada' => $ket['diunduh'],
        ] as $label => $isi) {
            $this->tulis($ws, 2, $r, $label);
            $this->tulis($ws, 3, $r, $isi);
            $ws->mergeCells("C{$r}:E{$r}");
            $ws->getStyle("B{$r}")->getFont()->setBold(true)->getColor()->setRGB(self::REDUP);
            $r++;
        }

        $r = $this->bagian($ws, $r + 1, 'Angka utama', 4);
        $this->tabel($ws, $r, [
            ['Indikator', 36],
            ['Nilai', 16, self::FORMAT_KG],
            ['Satuan', 10, null, 'tengah'],
            ['Keterangan', 42],
        ], [
            ['Uang dari jamur terjual', $penjualan['rupiah'], 'Rp', 'Dari penjualan jamur pada rentang ini'],
            ['Panen kopi', $kopi['panen'], 'kg', count($kopi['per_jenis']).' jenis · '.count($kopi['per_tempat']).' tempat panen'],
            ['Limbah kopi (cangkang)', $kopi['limbah'], 'kg', FormatHasil::persen($kopi['persen_limbah']).' dari panen kopi'],
            ['Panen jamur', $jamur['total'], 'kg', 'Semua media tanam'],
            ['Panen jamur dengan cangkang kopi', $jamur['cangkang'], 'kg', FormatHasil::persen($jamur['persen_cangkang']).' dari panen jamur'],
            ['Jamur terjual', $penjualan['kg'], 'kg', 'Sudah sampai ke pembeli'],
        ], formatBaris: [0 => self::FORMAT_RUPIAH]);

        $this->catatan($ws, $ws->getHighestRow() + 2, 'Sumber: catatan pengelola Desa Lumeneng, Program KKN / Aksara Peduli. Lembar lain di berkas ini berisi rincian per bulan, per jenis, dan data harian.', 4);
    }

    private function lembarPerkembangan(Worksheet $ws, array $ket, array $deret): void
    {
        $this->kepala($ws, 'Perkembangan', 'Perkembangan Hasil Desa', $ket['baris'], 5);
        $kolom = [['Periode', 22], ['Panen kopi (kg)', 18, self::FORMAT_KG], ['Panen jamur (kg)', 18, self::FORMAT_KG], ['Jamur terjual (kg)', 18, self::FORMAT_KG], ['Pendapatan (Rp)', 20, self::FORMAT_RUPIAH]];

        if (! $deret['ada']) {
            $this->catatan($ws, 4, 'Belum ada catatan pada rentang ini.', 5);

            return;
        }

        $r = 4;
        foreach (['bulan' => 'Per bulan', 'tahun' => 'Per tahun'] as $kunci => $judul) {
            $skala = $deret['skala'][$kunci];
            $r = $this->bagian($ws, $r, $judul, 5);
            $baris = [];
            foreach ($skala['lengkap'] as $i => $periode) {
                $baris[] = [$periode, $skala['kopi'][$i], $skala['jamur'][$i], $skala['terjual'][$i], $skala['pendapatan'][$i]];
            }
            $r = $this->tabel($ws, $r, $kolom, $baris, total: ['Jumlah', ...array_map(fn (string $s) => round(array_sum($skala[$s]), 2), ['kopi', 'jamur', 'terjual', 'pendapatan'])]);
            if ($skala['terpotong']) {
                $r = $this->catatan($ws, $r - 1, 'Hanya periode terbaru yang ditampilkan; tabel ini dibatasi supaya tetap ringan.', 5) + 1;
            }
        }
    }

    private function lembarPanenKopi(Worksheet $ws, array $ket, $daftar): void
    {
        $this->kepala($ws, 'Panen Kopi', 'Catatan Timbang Panen Kopi', $ket['baris'].' · Berat bersih = berat kotor dikurangi limbah', 8);
        if ($daftar->isEmpty()) {
            $this->catatan($ws, 4, 'Belum ada catatan panen kopi pada rentang ini.', 8);

            return;
        }

        $baris = [];
        foreach ($daftar->values() as $i => $p) {
            $kotor = (float) $p->jumlah_kg;
            $limbah = (float) $p->limbah_kg;
            $baris[] = [$i + 1, $p->tanggal_panen, $p->nama_petani, $p->tempat_panen, $p->jenis_kopi, $kotor, $limbah, max(round($kotor - $limbah, 2), 0.0)];
        }
        $kotor = round((float) $daftar->sum('jumlah_kg'), 2);
        $limbah = round((float) $daftar->sum('limbah_kg'), 2);

        $this->tabel($ws, 4, [
            ['No', 6, self::FORMAT_BILANGAN, 'tengah'], ['Tanggal timbang', 16, self::FORMAT_TANGGAL, 'tengah'], ['Nama petani/kelompok', 28], ['Blok lahan', 24], ['Jenis kopi', 18],
            ['Berat kotor (kg)', 16, self::FORMAT_KG], ['Limbah (kg)', 14, self::FORMAT_KG], ['Berat bersih (kg)', 16, self::FORMAT_KG],
        ], $baris, total: ['', '', 'Jumlah', '', '', $kotor, $limbah, max(round($kotor - $limbah, 2), 0.0)], penyaring: true, bekukan: true);
    }

    private function lembarRekapKopi(Worksheet $ws, array $ket, array $kopi): void
    {
        $this->kepala($ws, 'Rekap Kopi', 'Rekap Panen Kopi', $ket['baris'], 4);
        if ($kopi['panen'] <= 0 && $kopi['limbah'] <= 0) {
            $this->catatan($ws, 4, 'Belum ada catatan panen kopi pada rentang ini.', 4);

            return;
        }

        $r = 4;
        foreach (['per_jenis' => ['Per jenis kopi', 'Jenis kopi'], 'per_tempat' => ['Per tempat panen', 'Tempat panen'], 'per_tahun' => ['Per tahun', 'Tahun'], 'per_bulan' => ['Per bulan', 'Bulan']] as $kunci => [$judul, $kepalaKolom]) {
            $r = $this->bagian($ws, $r, $judul, 4);
            $baris = array_map(fn (array $k) => [$k['label'], $k['panen'], $k['limbah'], $k['panen'] > 0 ? $k['limbah'] / $k['panen'] : 0.0], $kopi[$kunci]);
            $r = $this->tabel($ws, $r, [
                [$kepalaKolom, 28], ['Panen (kg)', 16, self::FORMAT_KG], ['Limbah (kg)', 16, self::FORMAT_KG], ['Limbah (%)', 14, self::FORMAT_PERSEN],
            ], $baris, total: ['Jumlah', $kopi['panen'], $kopi['limbah'], $kopi['panen'] > 0 ? $kopi['limbah'] / $kopi['panen'] : 0.0]);
        }
    }

    private function lembarPanenJamur(Worksheet $ws, array $ket, $daftar): void
    {
        $this->kepala($ws, 'Panen Jamur', 'Data Panen Jamur', $ket['waktu_saja'], 6);
        if ($daftar->isEmpty()) {
            $this->catatan($ws, 4, 'Belum ada catatan panen jamur pada rentang ini.', 6);

            return;
        }

        $baris = [];
        foreach ($daftar->values() as $i => $p) {
            $kg = (float) $p->jumlah_kg;
            $baglog = $p->jumlah_baglog;
            $baris[] = [$i + 1, $p->tanggal_panen, RekapHasil::MEDIA[(int) $p->dengan_cangkang_kopi], $baglog, $kg, $baglog ? round($kg / $baglog, 2) : null];
        }

        $this->tabel($ws, 4, [
            ['No', 6, self::FORMAT_BILANGAN, 'tengah'], ['Tanggal panen', 16, self::FORMAT_TANGGAL, 'tengah'], ['Media tanam', 26],
            ['Jumlah baglog', 16, self::FORMAT_BILANGAN], ['Panen (kg)', 14, self::FORMAT_KG], ['Hasil per baglog (kg)', 22, self::FORMAT_KG],
        ], $baris, total: ['', '', 'Jumlah', (int) $daftar->sum('jumlah_baglog'), round((float) $daftar->sum('jumlah_kg'), 2), ''], penyaring: true, bekukan: true);
    }

    private function lembarPenjualanJamur(Worksheet $ws, array $ket, $daftar): void
    {
        $this->kepala($ws, 'Penjualan Jamur', 'Data Penjualan Jamur', $ket['waktu_saja'], 4);
        if ($daftar->isEmpty()) {
            $this->catatan($ws, 4, 'Belum ada catatan penjualan jamur pada rentang ini.', 4);

            return;
        }

        $baris = [];
        foreach ($daftar->values() as $i => $p) {
            $media = $p->dengan_cangkang_kopi === null ? 'Belum dicatat' : RekapHasil::MEDIA[(int) $p->dengan_cangkang_kopi];
            $baris[] = [$i + 1, $p->tanggal_penjualan, $media, (float) $p->jumlah_kg];
        }

        $this->tabel($ws, 4, [
            ['No', 6, self::FORMAT_BILANGAN, 'tengah'], ['Tanggal penjualan', 18, self::FORMAT_TANGGAL, 'tengah'], ['Media tanam', 26], ['Terjual (kg)', 16, self::FORMAT_KG],
        ], $baris, total: ['', '', 'Jumlah', round((float) $daftar->sum('jumlah_kg'), 2)], penyaring: true, bekukan: true);
    }

    private function lembarBaglog(Worksheet $ws, array $ket, array $perbandingan, array $kesimpulan): void
    {
        $this->kepala($ws, 'Perbandingan Baglog', 'Apakah cangkang kopi membantu jamur?', 'Hasil jamur per baglog (kantong media tanam), dengan dan tanpa cangkang kopi. '.$ket['waktu_saja'], 5);

        $baris = array_map(fn (array $m) => [$m['label'], $m['baglog'], $m['panen'], $m['kg_per_baglog'], $m['terjual_kg']], $perbandingan);
        $r = $this->tabel($ws, 4, [
            ['Media tanam', 28], ['Jumlah baglog', 16, self::FORMAT_BILANGAN], ['Panen jamur (kg)', 18, self::FORMAT_KG], ['Hasil per baglog (kg)', 22, self::FORMAT_KG], ['Jamur terjual (kg)', 18, self::FORMAT_KG],
        ], $baris);

        $r = $this->bagian($ws, $r, 'Kesimpulan', 5);
        if ($kesimpulan['selisih']) {
            $this->tulis($ws, 2, $r, $kesimpulan['selisih']);
            $ws->getStyle("B{$r}")->getFont()->setBold(true)->setSize(14)->getColor()->setRGB(self::HIJAU_TUA);
            $r++;
        }
        $r = $this->catatan($ws, $r, $kesimpulan['kalimat'], 5, terang: false);
        $this->catatan($ws, $r, 'Hasil per baglog hanya menghitung catatan yang menyebut jumlah baglog. Catatan lama tanpa jumlah baglog tidak ikut dibagi.', 5);
    }

    // ───────────────────────────── Bahan penyusun ─────────────────────────────

    /**
     * @return array{waktu: string, jenis: string, tempat: string, diunduh: string, baris: string, waktu_saja: string}
     */
    private function keteranganFilter(array $filter): array
    {
        $dari = $filter['dari'] ?? null;
        $sampai = $filter['sampai'] ?? null;
        $waktu = match (true) {
            (bool) ($dari && $sampai) => FormatHasil::tanggal($dari).' sampai '.FormatHasil::tanggal($sampai),
            (bool) $dari => 'Sejak '.FormatHasil::tanggal($dari),
            (bool) $sampai => 'Sampai '.FormatHasil::tanggal($sampai),
            default => 'Semua waktu',
        };
        $jenis = ($filter['jenis'] ?? null) ?: 'Semua jenis';
        $tempat = ($filter['tempat'] ?? null) ?: 'Semua tempat';

        return [
            'waktu' => $waktu,
            'jenis' => $jenis,
            'tempat' => $tempat,
            'diunduh' => now()->format('d-m-Y H:i').' WIB',
            'baris' => "Rentang waktu: {$waktu} · Jenis kopi: {$jenis} · Tempat: {$tempat}",
            'waktu_saja' => "Rentang waktu: {$waktu}",
        ];
    }

    /**
     * Judul lembar. Kolom A dibiarkan sempit sebagai tepi kiri, isi mulai kolom B.
     */
    private function kepala(Worksheet $ws, string $namaLembar, string $judul, string $subjudul, int $jumlahKolom): void
    {
        $ws->setTitle($namaLembar);
        $ws->getTabColor()->setRGB(self::HIJAU_TUA);
        $ws->setShowGridlines(false);
        $ws->getColumnDimension('A')->setWidth(2);

        $akhir = Coordinate::stringFromColumnIndex($jumlahKolom + 1);
        $this->tulis($ws, 2, 1, $judul);
        $ws->mergeCells("B1:{$akhir}1");
        $ws->getStyle('B1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB(self::HIJAU_TUA);
        $ws->getRowDimension(1)->setRowHeight(30);
        $ws->getStyle('B1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $this->tulis($ws, 2, 2, $subjudul);
        $ws->mergeCells("B2:{$akhir}2");
        $ws->getStyle('B2')->getFont()->setItalic(true)->getColor()->setRGB(self::REDUP);
        $ws->getStyle('B2')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $ws->getRowDimension(2)->setRowHeight(strlen($subjudul) > 95 ? 32 : 18);

        $ws->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
        $ws->getSheetView()->setZoomScale(100);
    }

    private function bagian(Worksheet $ws, int $baris, string $teks, int $jumlahKolom): int
    {
        $akhir = Coordinate::stringFromColumnIndex($jumlahKolom + 1);
        $this->tulis($ws, 2, $baris, $teks);
        $ws->mergeCells("B{$baris}:{$akhir}{$baris}");
        $gaya = $ws->getStyle("B{$baris}:{$akhir}{$baris}");
        $gaya->getFont()->setBold(true)->setSize(12)->getColor()->setRGB(self::HIJAU_TUA);
        $gaya->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::HIJAU_TUA);
        $ws->getRowDimension($baris)->setRowHeight(22);

        return $baris + 1;
    }

    private function catatan(Worksheet $ws, int $baris, string $teks, int $jumlahKolom, bool $terang = true): int
    {
        $this->tulis($ws, 2, $baris, $teks);
        $akhir = Coordinate::stringFromColumnIndex($jumlahKolom + 1);
        $ws->mergeCells("B{$baris}:{$akhir}{$baris}");
        $ws->getStyle("B{$baris}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        if ($terang) {
            $ws->getStyle("B{$baris}")->getFont()->setItalic(true)->getColor()->setRGB(self::REDUP);
        }
        $ws->getRowDimension($baris)->setRowHeight(strlen($teks) > 110 ? 34 : 20);

        return $baris + 1;
    }

    /**
     * Tabel berkepala hijau mulai kolom B. Mengembalikan nomor baris kosong berikutnya (sudah diberi jarak satu baris).
     *
     * @param  array<int, array{0: string, 1: int, 2?: ?string, 3?: string}>  $kolom  judul, lebar, format angka, rata (kiri/tengah/kanan)
     * @param  array<int, array<int, mixed>>  $baris
     * @param  ?array<int, mixed>  $total
     * @param  array<int, string>  $formatBaris  format khusus per nomor baris data (0 = baris pertama)
     */
    private function tabel(Worksheet $ws, int $mulai, array $kolom, array $baris, ?array $total = null, bool $penyaring = false, bool $bekukan = false, array $formatBaris = []): int
    {
        $jumlah = count($kolom);
        $awalKol = 2;
        $akhirKol = $awalKol + $jumlah - 1;
        $hurufAwal = Coordinate::stringFromColumnIndex($awalKol);
        $hurufAkhir = Coordinate::stringFromColumnIndex($akhirKol);

        foreach ($kolom as $i => [$judul, $lebar]) {
            $huruf = Coordinate::stringFromColumnIndex($awalKol + $i);
            $dimensi = $ws->getColumnDimension($huruf);
            $dimensi->setWidth(max($lebar, $dimensi->getWidth() > 0 ? $dimensi->getWidth() : 0));
            $this->tulis($ws, $awalKol + $i, $mulai, $judul);
        }
        $kepala = $ws->getStyle("{$hurufAwal}{$mulai}:{$hurufAkhir}{$mulai}");
        $kepala->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $kepala->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HIJAU_TUA);
        $kepala->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $ws->getRowDimension($mulai)->setRowHeight(30);

        $r = $mulai + 1;
        foreach ($baris as $n => $isi) {
            foreach ($isi as $i => $nilai) {
                $this->tulis($ws, $awalKol + $i, $r, $nilai);
            }
            $this->gayaBaris($ws, $r, $kolom, $awalKol, $formatBaris[$n] ?? null);
            if ($n % 2 === 1) {
                $ws->getStyle("{$hurufAwal}{$r}:{$hurufAkhir}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F6FAF7');
            }
            $r++;
        }
        $akhirData = $r - 1;

        if ($total !== null) {
            foreach ($total as $i => $nilai) {
                $this->tulis($ws, $awalKol + $i, $r, $nilai);
            }
            $this->gayaBaris($ws, $r, $kolom, $awalKol, null);
            $gayaTotal = $ws->getStyle("{$hurufAwal}{$r}:{$hurufAkhir}{$r}");
            $gayaTotal->getFont()->setBold(true);
            $gayaTotal->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HIJAU_MUDA);
            $gayaTotal->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::HIJAU_TUA);
            $r++;
        }

        if ($penyaring && $akhirData > $mulai) {
            $ws->setAutoFilter("{$hurufAwal}{$mulai}:{$hurufAkhir}{$akhirData}");
        }
        if ($bekukan) {
            $ws->freezePane('A'.($mulai + 1));
        }

        return $r + 1;
    }

    private function gayaBaris(Worksheet $ws, int $r, array $kolom, int $awalKol, ?string $formatKhusus): void
    {
        foreach ($kolom as $i => $def) {
            $huruf = Coordinate::stringFromColumnIndex($awalKol + $i);
            $sel = $ws->getStyle("{$huruf}{$r}");
            $format = ($i === 1 ? $formatKhusus : null) ?? ($def[2] ?? null);
            if ($format) {
                $sel->getNumberFormat()->setFormatCode($format);
            }
            $rata = $def[3] ?? ($format ? 'kanan' : 'kiri');
            $sel->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(match ($rata) {
                'tengah' => Alignment::HORIZONTAL_CENTER,
                'kanan' => Alignment::HORIZONTAL_RIGHT,
                default => Alignment::HORIZONTAL_LEFT,
            });
            $sel->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::GARIS);
        }
        $ws->getRowDimension($r)->setRowHeight(20);
    }

    /**
     * Semua teks ditulis eksplisit sebagai teks, supaya isian pengguna yang diawali "=" tidak jadi rumus.
     */
    private function tulis(Worksheet $ws, int $kolom, int $baris, mixed $nilai): void
    {
        $sel = Coordinate::stringFromColumnIndex($kolom).$baris;

        match (true) {
            $nilai === null => $ws->setCellValueExplicit($sel, '–', DataType::TYPE_STRING),
            $nilai instanceof CarbonInterface => $ws->setCellValueExplicit($sel, Date::PHPToExcel($nilai->copy()->startOfDay()), DataType::TYPE_NUMERIC),
            is_int($nilai) || is_float($nilai) => $ws->setCellValueExplicit($sel, $nilai, DataType::TYPE_NUMERIC),
            default => $ws->setCellValueExplicit($sel, (string) $nilai, DataType::TYPE_STRING),
        };
    }
}
