<?php

namespace App\Models\Ews;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Titik di peta yang TIDAK mengirim data sensor ke web (beda dengan Wilayah). */
class Titik extends Model
{
    use HasFactory;

    public const JENIS_ALARM = 'alarm';

    public const JENIS_RAWAN = 'rawan';

    /** @var array<string, array{nama: string, keterangan: string}> */
    public const JENIS = [
        self::JENIS_ALARM => ['nama' => 'EWS alarm', 'keterangan' => 'Alat EWS dengan alarm, belum terhubung ke web.'],
        self::JENIS_RAWAN => ['nama' => 'Titik rawan longsor', 'keterangan' => 'Lokasi rawan longsor, tanpa alat.'],
    ];

    protected $table = 'ews_titik';

    protected $fillable = ['nama', 'jenis', 'lintang', 'bujur', 'poligon'];

    protected function casts(): array
    {
        return [
            'poligon' => 'array',
            'lintang' => 'float',
            'bujur' => 'float',
        ];
    }

    public function namaJenis(): string
    {
        return self::JENIS[$this->jenis]['nama'] ?? $this->jenis;
    }
}
