<?php

namespace App\Services\Toko;

/**
 * Menyiapkan Google Sheet sekali di awal: membuat tab, menulis judul kolom, dan memformat kolom
 * (tanggal dipaksa jadi teks supaya "05-10-2026" tidak ditebak Sheets sebagai bulan-hari).
 * Aman dijalankan ulang; tab dan isian yang sudah ada tidak dihapus.
 */
class PenyiapSheetHasil
{
    private const HIJAU = ['red' => 0.12, 'green' => 0.35, 'blue' => 0.23];

    private const ABU = ['red' => 0.93, 'green' => 0.93, 'blue' => 0.93];

    public function __construct(private GoogleSheets $sheets) {}

    public function siapkan(): void
    {
        $ada = $this->sheets->lembar();

        $tambah = [];
        foreach (ImporExcelHasil::kunci() as $jenis) {
            if (! isset($ada[SinkronSheetHasil::namaTab($jenis)])) {
                $tambah[] = ['addSheet' => ['properties' => ['title' => SinkronSheetHasil::namaTab($jenis)]]];
            }
        }
        if ($tambah !== []) {
            $this->sheets->ubahStruktur($tambah);
            $ada = $this->sheets->lembar();
        }

        $this->sheets->tulis(array_map(fn (string $j) => [
            'range' => "'".SinkronSheetHasil::namaTab($j)."'!A1:".SinkronSheetHasil::huruf($j).'1',
            'values' => [SinkronSheetHasil::kepala($j)],
        ], ImporExcelHasil::kunci()));

        $format = [];
        foreach (ImporExcelHasil::kunci() as $jenis) {
            array_push($format, ...$this->formatTab($jenis, $ada[SinkronSheetHasil::namaTab($jenis)]));
        }
        $this->sheets->ubahStruktur($format);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatTab(string $jenis, int $idLembar): array
    {
        $kolom = array_values(ImporExcelHasil::JENIS[$jenis]['kolom']);
        $terakhir = count($kolom) + 1;

        $rentang = fn (int $dari, int $sampai, int $barisAwal = 0, ?int $barisAkhir = null) => array_filter([
            'sheetId' => $idLembar,
            'startRowIndex' => $barisAwal,
            'endRowIndex' => $barisAkhir,
            'startColumnIndex' => $dari,
            'endColumnIndex' => $sampai,
        ], fn ($n) => $n !== null);

        $permintaan = [
            ['updateSheetProperties' => ['properties' => ['sheetId' => $idLembar, 'gridProperties' => ['frozenRowCount' => 1]], 'fields' => 'gridProperties.frozenRowCount']],
            ['repeatCell' => [
                'range' => $rentang(0, $terakhir + 1, 0, 1),
                'cell' => ['userEnteredFormat' => ['backgroundColor' => self::HIJAU, 'textFormat' => ['bold' => true, 'foregroundColor' => ['red' => 1, 'green' => 1, 'blue' => 1]]]],
                'fields' => 'userEnteredFormat(backgroundColor,textFormat)',
            ]],
            // ID dan Status diisi sistem; warna abu-abu sebagai tanda "jangan diubah".
            ['repeatCell' => ['range' => $rentang(0, 1, 1), 'cell' => ['userEnteredFormat' => ['backgroundColor' => self::ABU]], 'fields' => 'userEnteredFormat.backgroundColor']],
            ['repeatCell' => ['range' => $rentang($terakhir, $terakhir + 1, 1), 'cell' => ['userEnteredFormat' => ['backgroundColor' => self::ABU]], 'fields' => 'userEnteredFormat.backgroundColor']],
            ['updateDimensionProperties' => ['range' => ['sheetId' => $idLembar, 'dimension' => 'COLUMNS', 'startIndex' => $terakhir, 'endIndex' => $terakhir + 1], 'properties' => ['pixelSize' => 320], 'fields' => 'pixelSize']],
        ];

        foreach ($kolom as $i => $k) {
            $indeks = $i + 1;
            if ($k['tipe'] === 'tanggal') {
                $permintaan[] = ['repeatCell' => ['range' => $rentang($indeks, $indeks + 1, 1), 'cell' => ['userEnteredFormat' => ['numberFormat' => ['type' => 'TEXT']]], 'fields' => 'userEnteredFormat.numberFormat']];
            }
            if ($k['tipe'] === 'media') {
                $permintaan[] = ['setDataValidation' => [
                    'range' => $rentang($indeks, $indeks + 1, 1),
                    'rule' => [
                        'condition' => ['type' => 'ONE_OF_LIST', 'values' => array_map(fn (string $v) => ['userEnteredValue' => $v], array_values(RekapHasil::MEDIA))],
                        'showCustomUi' => true,
                        'strict' => true,
                    ],
                ]];
            }
        }

        return $permintaan;
    }
}
