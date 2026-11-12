<?php

namespace App\Services\Ews;

class GeometriPoligon
{
    public const MAKS_TITIK = 200;

    // Sekitar belasan meter persegi; di bawah ini titik praktis segaris atau salah ketuk.
    private const LUAS_MINIMAL = 1e-9;

    /**
     * @return array<int, array{0: float, 1: float}>|null null bila bukan daftar koordinat yang sah
     */
    public static function normalkan(mixed $titik): ?array
    {
        if (! is_array($titik) || ! array_is_list($titik)) {
            return null;
        }

        $hasil = [];
        foreach ($titik as $t) {
            if (! is_array($t) || ! array_is_list($t) || count($t) !== 2 || ! is_numeric($t[0]) || ! is_numeric($t[1]) || abs((float) $t[0]) > 90 || abs((float) $t[1]) > 180) {
                return null;
            }
            $hasil[] = [round((float) $t[0], 6), round((float) $t[1], 6)];
        }

        // Alat gambar peta sering mengulang titik pertama di akhir untuk menutup bentuk.
        if (count($hasil) > 1 && $hasil[0] === end($hasil)) {
            array_pop($hasil);
        }

        return $hasil;
    }

    /**
     * Menerima dua bentuk isian: JSON [[lintang,bujur],...] atau satu titik per baris "lintang, bujur"
     * (yang biasa disalin dari Google Maps). Spasi nyasar di dalam angka diabaikan.
     *
     * @return array<int, array{0: float, 1: float}>|null null bila bukan daftar koordinat yang sah
     */
    public static function dariTeks(string $teks): ?array
    {
        $teks = trim($teks);

        if ($teks === '') {
            return null;
        }
        if (str_starts_with($teks, '[')) {
            return self::normalkan(json_decode($teks, true));
        }

        $titik = [];
        foreach (preg_split('/\R/', $teks) as $baris) {
            if (trim($baris) === '') {
                continue;
            }
            $bagian = preg_split('/[,;]/', $baris);
            if (count($bagian) !== 2) {
                return null;
            }
            $titik[] = array_map(fn (string $b) => preg_replace('/\s+/', '', $b), $bagian);
        }

        return self::normalkan($titik);
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $titik
     * @return string|null pesan masalah, atau null bila bentuknya sah
     */
    public static function masalah(array $titik): ?string
    {
        if (count($titik) > self::MAKS_TITIK) {
            return 'Terlalu banyak titik (maksimal '.self::MAKS_TITIK.').';
        }
        if (count(array_unique(array_map('json_encode', $titik))) < 3) {
            return 'Daerah harus berbentuk bangun dengan minimal 3 titik berbeda.';
        }
        // Silang diperiksa lebih dulu: bentuk pita simetris luasnya nol dan bisa keliru disebut segaris.
        if (self::menyilang($titik)) {
            return 'Sisi-sisi bangun saling bersilangan. Gambar ulang tanpa menyilang.';
        }
        if (self::luas($titik) < self::LUAS_MINIMAL) {
            return 'Titik-titiknya segaris sehingga membentuk garis, bukan bangun.';
        }

        return null;
    }

    // Rumus tali sepatu.
    private static function luas(array $p): float
    {
        $jumlah = 0.0;
        $n = count($p);

        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $jumlah += $p[$i][0] * $p[$j][1] - $p[$j][0] * $p[$i][1];
        }

        return abs($jumlah) / 2;
    }

    private static function menyilang(array $p): bool
    {
        $n = count($p);

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                // Sisi bersebelahan memang bertemu di sudut.
                if ($j === $i + 1 || ($i === 0 && $j === $n - 1)) {
                    continue;
                }
                if (self::sisiMemotong($p[$i], $p[($i + 1) % $n], $p[$j], $p[($j + 1) % $n])) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function sisiMemotong(array $a, array $b, array $c, array $d): bool
    {
        $arah = fn (array $p, array $q, array $r) => ($q[0] - $p[0]) * ($r[1] - $p[1]) - ($q[1] - $p[1]) * ($r[0] - $p[0]);

        return $arah($a, $b, $c) * $arah($a, $b, $d) < 0 && $arah($c, $d, $a) * $arah($c, $d, $b) < 0;
    }
}
