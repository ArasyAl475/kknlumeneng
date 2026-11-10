<?php

namespace App\Services\Ews;

class Sensor
{
    // Keterangan tampilan tiap sensor. Aturan penilaiannya tetap di PenilaianStatus.
    public const DAFTAR = [
        'kelembapan_tanah' => ['nama' => 'Kelembapan tanah', 'satuan' => '%', 'ikon' => 'tanah'],
        'intensitas_hujan' => ['nama' => 'Intensitas hujan', 'satuan' => '%', 'ikon' => 'hujan'],
        'kemiringan' => ['nama' => 'Kemiringan', 'satuan' => '°', 'ikon' => 'miring'],
        'kelembapan_udara' => ['nama' => 'Kelembapan udara', 'satuan' => '%', 'ikon' => 'udara'],
    ];

    public static function teks(string $sensor, float|int|null $nilai): string
    {
        if ($nilai === null) {
            return __('ews.level.kosong');
        }

        return PenilaianStatus::angka(PenilaianStatus::nilaiDinilai($sensor, $nilai)).self::DAFTAR[$sensor]['satuan'];
    }
}
