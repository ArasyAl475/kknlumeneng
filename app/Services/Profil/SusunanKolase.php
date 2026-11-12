<?php

namespace App\Services\Profil;

/**
 * Susunan kolase foto ala dinding museum: foto dengan bentuk berbeda (tegak, mendatar, kotak, jalur panjang)
 * saling mengisi sebuah kotak besar tanpa celah. Kotaknya dibagi 6 kolom x 6 baris; tiap foto menempati
 * sebuah persegi panjang sel. Hasil dipakai sebagai CSS grid-area: "baris-awal / kolom-awal / baris-akhir / kolom-akhir".
 *
 * Tiap susunan wajib menutup 36 sel persis satu kali (diuji di SusunanKolaseTest), jadi tidak ada ruang kosong.
 * Foto pertama selalu yang terbesar, jadi foto utama potensi tampil paling menonjol.
 */
final class SusunanKolase
{
    // Paling banyak foto yang digambar di kolase; sisanya tetap bisa dilihat lewat penampil foto.
    public const MAKS_TAMPIL = 6;

    private const KOLOM = 6;

    private const BARIS = 6;

    /**
     * @var array<int, array<int, string>>
     */
    private const POLA = [
        1 => ['1 / 1 / 7 / 7'],
        2 => ['1 / 1 / 7 / 5', '1 / 5 / 7 / 7'],
        3 => ['1 / 1 / 7 / 4', '1 / 4 / 5 / 7', '5 / 4 / 7 / 7'],
        4 => ['1 / 1 / 5 / 5', '1 / 5 / 5 / 7', '5 / 1 / 7 / 4', '5 / 4 / 7 / 7'],
        5 => ['1 / 1 / 4 / 5', '1 / 5 / 5 / 7', '4 / 1 / 7 / 3', '4 / 3 / 7 / 5', '5 / 5 / 7 / 7'],
        6 => ['1 / 1 / 5 / 4', '1 / 4 / 3 / 7', '3 / 4 / 5 / 6', '3 / 6 / 7 / 7', '5 / 1 / 7 / 3', '5 / 3 / 7 / 6'],
    ];

    /**
     * @return array<int, string> letak tiap foto; panjangnya min(jumlah, MAKS_TAMPIL)
     */
    public static function area(int $jumlahFoto): array
    {
        $dipakai = max(1, min($jumlahFoto, self::MAKS_TAMPIL));

        return self::POLA[$dipakai];
    }

    /**
     * Daftar sel (baris,kolom) yang ditutup sebuah area, untuk pemeriksaan di tes.
     *
     * @return array<int, string>
     */
    public static function sel(string $area): array
    {
        [$barisAwal, $kolomAwal, $barisAkhir, $kolomAkhir] = array_map('intval', explode('/', $area));
        $hasil = [];

        for ($b = $barisAwal; $b < $barisAkhir; $b++) {
            for ($k = $kolomAwal; $k < $kolomAkhir; $k++) {
                $hasil[] = "{$b},{$k}";
            }
        }

        return $hasil;
    }

    public static function jumlahSel(): int
    {
        return self::KOLOM * self::BARIS;
    }
}
