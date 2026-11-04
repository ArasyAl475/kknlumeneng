<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';
    protected $primaryKey = 'kunci';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kunci', 'nilai'];

    // Ambang batas yang berlaku sekarang (dari database, cadangan dari config/ews.php)
    public static function ambang(): array
    {
        $data = static::pluck('nilai', 'kunci');
        $ambil = fn (string $kunci) => (float) ($data[$kunci] ?? config('ews.' . $kunci));

        return [
            // kelembapan tanah (%)
            'waspada'        => (int) $ambil('batas_waspada'),
            'validasi'       => (int) $ambil('batas_validasi'),
            // curah hujan (mm per jam)
            'hujan_waspada'  => $ambil('batas_hujan_waspada'),
            'hujan_validasi' => $ambil('batas_hujan_validasi'),
            // kemiringan (derajat)
            'miring_waspada'  => $ambil('batas_miring_waspada'),
            'miring_validasi' => $ambil('batas_miring_validasi'),
        ];
    }

    public static function simpan(string $kunci, $nilai): void
    {
        static::updateOrCreate(['kunci' => $kunci], ['nilai' => (string) $nilai]);
    }
}
