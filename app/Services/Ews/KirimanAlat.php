<?php

namespace App\Services\Ews;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;

/**
 * Aturan isi kiriman alat, dipakai bersama oleh API HTTP dan penerima MQTT supaya kedua jalur
 * tidak bisa berbeda diam-diam. Kontrak: docs/ews/API_ALAT.md.
 */
class KirimanAlat
{
    public const SENSOR = ['kelembapan_tanah', 'intensitas_hujan', 'kemiringan', 'kelembapan_udara'];

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function aturanSensor(): array
    {
        return [
            'kelembapan_tanah' => ['nullable', 'numeric', 'between:0,100'],
            'intensitas_hujan' => ['nullable', 'numeric', 'between:0,100'],
            'kemiringan' => ['nullable', 'numeric', 'between:-90,90'],
            'kelembapan_udara' => ['nullable', 'numeric', 'between:0,100'],
            'kabel_putus' => ['nullable', 'boolean'],
        ];
    }

    // Firmware lama masih mengirim nama "kelembapan".
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalisasi(array $data): array
    {
        if (($data['kelembapan_tanah'] ?? null) === null && ($data['kelembapan'] ?? null) !== null) {
            $data['kelembapan_tanah'] = $data['kelembapan'];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function periksaAdaIsi(ValidatorContract $validator, array $data): void
    {
        $adaSensor = collect(self::SENSOR)->contains(fn (string $sensor) => ($data[$sensor] ?? null) !== null);
        $kabelPutus = filter_var($data['kabel_putus'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $adaSensor && ! $kabelPutus) {
            $validator->errors()->add('kelembapan_tanah', 'Kirim minimal satu nilai sensor atau kabel_putus: true.');
        }
    }

    /**
     * Validator untuk isi kiriman yang datang bukan dari HTTP (mis. MQTT). Hanya field sensor;
     * wilayah ditentukan pemanggil.
     *
     * @param  array<string, mixed>  $data
     */
    public static function validator(array $data): ValidatorContract
    {
        $data = self::normalisasi($data);

        return Validator::make($data, self::aturanSensor())
            ->after(fn (ValidatorContract $v) => self::periksaAdaIsi($v, $data));
    }

    /**
     * @param  array<string, mixed>  $tervalidasi
     * @return array<string, float|null>
     */
    public static function bacaan(array $tervalidasi): array
    {
        $bacaan = [];

        foreach (self::SENSOR as $sensor) {
            $nilai = $tervalidasi[$sensor] ?? null;
            $bacaan[$sensor] = $nilai === null ? null : round((float) $nilai, 2);
        }

        return $bacaan;
    }
}
