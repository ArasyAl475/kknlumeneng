<?php

namespace App\Services\Ews;

use Carbon\CarbonInterface;

/**
 * Satu-satunya tempat aturan status EWS (docs/ews/ATURAN_EWS.md). Sengaja murni tanpa basis data
 * agar seluruh aturan bisa diuji cepat dan tidak ada tempat lain yang tergoda menghitung ulang.
 *
 * Level sensor: null = tidak mengirim, 0 = normal, 1 = waspada, 2 = melewati batas validasi.
 */
class PenilaianStatus
{
    public const AMAN = 'Aman';

    public const WASPADA = 'Waspada';

    public const AWAS = 'Awas';

    public const WARNA = [self::AMAN => 'hijau', self::WASPADA => 'kuning', self::AWAS => 'merah'];

    public const WARNA_KOSONG = 'kosong';

    // Kelembapan udara sengaja tidak ada di sini: hanya ditampilkan, tidak ikut menentukan status.
    public const SENSOR_DINILAI = ['kelembapan_tanah', 'intensitas_hujan', 'kemiringan'];

    private const PERINGKAT = [self::WARNA_KOSONG => 0, 'hijau' => 1, 'kuning' => 2, 'merah' => 3];

    /**
     * @param  array{waspada: float|int, validasi: float|int}  $batas
     */
    public static function level(?float $nilai, array $batas): ?int
    {
        if ($nilai === null) {
            return null;
        }

        return match (true) {
            $nilai >= $batas['validasi'] => 2,
            $nilai >= $batas['waspada'] => 1,
            default => 0,
        };
    }

    /**
     * @param  array<string, float|int|null>  $bacaan
     * @param  array<string, array{waspada: float|int, validasi: float|int}>  $ambang
     * @return array<string, int|null>
     */
    public static function levelSensor(array $bacaan, array $ambang): array
    {
        $level = [];

        foreach (self::SENSOR_DINILAI as $sensor) {
            $level[$sensor] = self::level(self::nilaiDinilai($sensor, $bacaan[$sensor] ?? null), $ambang[$sensor]);
        }

        return $level;
    }

    /**
     * @param  array<string, float|int|null>  $bacaan
     * @param  array<string, array{waspada: float|int, validasi: float|int}>  $ambang
     * @return array{status: string, warna: string, perlu_validasi: bool, pemicu: ?string, level: array<string, int|null>}
     */
    public static function nilai(array $bacaan, array $ambang, bool $kabelPutus = false): array
    {
        $level = self::levelSensor($bacaan, $ambang);
        $tertinggi = max([0, ...array_filter($level, fn (?int $lv) => $lv !== null)]);

        $status = match (true) {
            $kabelPutus => self::AWAS,
            $tertinggi >= 1 => self::WASPADA,
            default => self::AMAN,
        };

        $pemicu = [];
        if ($kabelPutus) {
            $pemicu[] = 'Kabel EWS putus';
        }
        foreach ($level as $sensor => $lv) {
            if ($lv !== null && $lv >= 1) {
                $pemicu[] = self::teksSensor($sensor, $bacaan[$sensor]);
            }
        }

        return [
            'status' => $status,
            'warna' => self::WARNA[$status],
            'perlu_validasi' => ! $kabelPutus && $tertinggi === 2,
            'pemicu' => $pemicu ? implode(', ', $pemicu) : null,
            'level' => $level,
        ];
    }

    /**
     * Status yang ditampilkan: Awas ditahan sampai dicabut admin walau data terbaru terlihat normal.
     *
     * @return array{status: ?string, warna: string}
     */
    public static function statusWilayah(?string $statusBacaanTerbaru, bool $statusAwas): array
    {
        $status = $statusAwas ? self::AWAS : $statusBacaanTerbaru;

        return ['status' => $status, 'warna' => $status === null ? self::WARNA_KOSONG : self::WARNA[$status]];
    }

    /**
     * Status dan pemicu yang ditampilkan untuk satu wilayah. Pembacaan yang tersimpan sebagai Awas
     * (disetujui admin atau kabel putus) dinilai ulang dari angkanya setelah Awas dicabut; kalau
     * tidak, wilayah tersangkut Awas sampai alat mengirim lagi, atau selamanya bila alat mati.
     *
     * @param  array<string, float|int|null>  $bacaan
     * @param  array<string, array{waspada: float|int, validasi: float|int}>  $ambang
     * @return array{status: ?string, warna: string, pemicu: ?string}
     */
    public static function statusTerkini(?string $statusTersimpan, ?string $pemicuTersimpan, array $bacaan, array $ambang, bool $statusAwas, ?string $sumberAwas = null): array
    {
        if ($statusAwas) {
            return self::statusWilayah($statusTersimpan, true) + ['pemicu' => $sumberAwas];
        }

        if ($statusTersimpan === self::AWAS) {
            $hasil = self::nilai($bacaan, $ambang);

            return ['status' => $hasil['status'], 'warna' => $hasil['warna'], 'pemicu' => $hasil['pemicu']];
        }

        return self::statusWilayah($statusTersimpan, false) + ['pemicu' => $pemicuTersimpan];
    }

    /**
     * @param  iterable<string>  $daftarWarna
     */
    public static function warnaTerburuk(iterable $daftarWarna): string
    {
        $terburuk = self::WARNA_KOSONG;

        foreach ($daftarWarna as $warna) {
            if (self::PERINGKAT[$warna] > self::PERINGKAT[$terburuk]) {
                $terburuk = $warna;
            }
        }

        return $terburuk;
    }

    public static function peringkat(string $warna): int
    {
        return self::PERINGKAT[$warna];
    }

    public static function alatOffline(?CarbonInterface $terakhirKirim, int $batasMenit, ?CarbonInterface $sekarang = null): bool
    {
        if ($terakhirKirim === null || $batasMenit <= 0) {
            return false;
        }

        return $terakhirKirim->diffInMinutes($sekarang ?? now(), true) >= $batasMenit;
    }

    /**
     * Alat yang diam saat wilayah sedang Waspada bukan berarti keadaan membaik: bisa jadi alatnya
     * rusak atau terkena longsor. Tidak jadi Awas otomatis (alat juga sering mati karena listrik atau
     * sinyal), tetapi warga diberi peringatan lebih keras dan admin diminta memutuskan.
     */
    public static function kondisiTakDiketahui(?string $status, bool $offline): bool
    {
        return $offline && $status === self::WASPADA;
    }

    public static function teksSensor(string $sensor, float|int|null $nilai): string
    {
        $angka = self::angka(self::nilaiDinilai($sensor, $nilai));

        return match ($sensor) {
            'kelembapan_tanah' => "Kelembapan tanah {$angka}%",
            'intensitas_hujan' => "Intensitas hujan {$angka}%",
            'kemiringan' => "Kemiringan {$angka}°",
            'kelembapan_udara' => "Kelembapan udara {$angka}%",
        };
    }

    // 24.0 -> "24", 12.35 -> "12,35" (tampilan memakai koma).
    public static function angka(float|int|null $nilai): string
    {
        return rtrim(rtrim(number_format((float) $nilai, 2, ',', ''), '0'), ',');
    }

    // Kemiringan dinilai dari besar simpangannya; arah negatif/positif sama saja.
    public static function nilaiDinilai(string $sensor, float|int|null $nilai): ?float
    {
        if ($nilai === null) {
            return null;
        }

        return $sensor === 'kemiringan' ? abs((float) $nilai) : (float) $nilai;
    }
}
