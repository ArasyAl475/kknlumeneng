<?php

namespace App\Models\Ews;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'ews_pengaturan';

    protected $primaryKey = 'kunci';

    protected $keyType = 'string';

    protected $fillable = ['kunci', 'nilai'];

    /**
     * Ambang yang berlaku: dari basis data, dengan cadangan config bila barisnya belum ada,
     * supaya penilaian tetap jalan walau seeder belum dijalankan.
     *
     * @return array<string, array{waspada: float, validasi: float}>
     */
    public static function ambang(): array
    {
        $tersimpan = static::query()->pluck('nilai', 'kunci');
        $ambang = [];

        foreach (config('ews.ambang_awal') as $sensor => $awal) {
            foreach (['waspada', 'validasi'] as $tingkat) {
                $ambang[$sensor][$tingkat] = (float) ($tersimpan["{$sensor}_{$tingkat}"] ?? $awal[$tingkat]);
            }
        }

        return $ambang;
    }

    /**
     * @param  array<string, array{waspada: float|int, validasi: float|int}>  $ambang
     */
    public static function simpanAmbang(array $ambang): void
    {
        foreach ($ambang as $sensor => $batas) {
            foreach ($batas as $tingkat => $nilai) {
                static::query()->updateOrCreate(['kunci' => "{$sensor}_{$tingkat}"], ['nilai' => (string) $nilai]);
            }
        }
    }
}
