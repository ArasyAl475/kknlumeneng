<?php

namespace App\Support;

/**
 * Satu-satunya tempat aturan status. Dipakai saat data masuk (EwsController)
 * dan saat data ditampilkan (supaya lencana tiap sensor ikut ambang batas terbaru).
 *
 * Level sensor: 0 = normal, 1 = waspada, 2 = melewati batas validasi, null = sensor tidak mengirim nilai.
 *
 * Aturan (sama seperti sebelumnya):
 *  - Kabel EWS putus                      -> Awas (merah), otomatis
 *  - Salah satu sensor >= batas validasi  -> Waspada (kuning) + menunggu validasi admin
 *  - Salah satu sensor >= batas waspada   -> Waspada (kuning)
 *  - selain itu                           -> Aman (hijau)
 * Kelembapan udara hanya ditampilkan, tidak ikut menentukan status.
 */
class PenilaianStatus
{
    public static function level(?float $nilai, float $waspada, float $validasi): ?int
    {
        if ($nilai === null) {
            return null;
        }

        return $nilai >= $validasi ? 2 : ($nilai >= $waspada ? 1 : 0);
    }

    // $d: ['kelembapan' => ?, 'curah_hujan' => ?, 'kemiringan' => ?]
    public static function levelSensor(array $d, array $ambang): array
    {
        $angka = fn ($v) => $v === null ? null : (float) $v;

        return [
            'tanah'  => self::level($angka($d['kelembapan'] ?? null), $ambang['waspada'], $ambang['validasi']),
            'hujan'  => self::level($angka($d['curah_hujan'] ?? null), $ambang['hujan_waspada'], $ambang['hujan_validasi']),
            'miring' => self::level(isset($d['kemiringan']) ? abs((float) $d['kemiringan']) : null, $ambang['miring_waspada'], $ambang['miring_validasi']),
        ];
    }

    // Mengembalikan ['status', 'warna', 'validasi' ('menunggu' atau null), 'pemicu', 'level']
    public static function nilai(array $d, array $ambang, bool $kabelPutus): array
    {
        $level  = self::levelSensor($d, $ambang);
        $teks   = [
            'tanah'  => fn () => 'Kelembapan tanah ' . self::angka($d['kelembapan']) . '%',
            'hujan'  => fn () => 'Curah hujan ' . self::angka($d['curah_hujan']) . ' mm/jam',
            'miring' => fn () => 'Kemiringan ' . self::angka(abs((float) $d['kemiringan'])) . '°',
        ];

        $pemicu = [];
        foreach ($level as $kunci => $lv) {
            if ($lv !== null && $lv >= 1) {
                $pemicu[] = $teks[$kunci]();
            }
        }
        if ($kabelPutus) {
            array_unshift($pemicu, 'Kabel EWS putus');
        }

        $tertinggi = max(array_merge([0], array_filter($level, fn ($x) => $x !== null)));

        if ($kabelPutus) {
            [$status, $warna, $validasi] = ['Awas', 'merah', null];
        } elseif ($tertinggi === 2) {
            [$status, $warna, $validasi] = ['Waspada', 'kuning', 'menunggu'];
        } elseif ($tertinggi === 1) {
            [$status, $warna, $validasi] = ['Waspada', 'kuning', null];
        } else {
            [$status, $warna, $validasi] = ['Aman', 'hijau', null];
        }

        return [
            'status'   => $status,
            'warna'    => $warna,
            'validasi' => $validasi,
            'pemicu'   => $pemicu ? implode(', ', $pemicu) : null,
            'level'    => $level,
        ];
    }

    // 24.0 -> "24", 12.35 -> "12,35"
    public static function angka($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
    }
}
