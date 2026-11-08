<?php

namespace App\Models\Toko;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    // Pengelola toko setelah KKN belum ditentukan, jadi nilai bawaan dibuat umum dan bisa diubah admin.
    public const BAWAAN = [
        'nama_penjual' => 'UMKM Desa Lumeneng',
        'keterangan_penjual' => 'Produk olahan warga Desa Lumeneng, Kec. Paninggaran, Kab. Pekalongan.',
        'lokasi_kirim' => 'Paninggaran, Kab. Pekalongan',
    ];

    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'toko_pengaturan';

    protected $primaryKey = 'kunci';

    protected $keyType = 'string';

    protected $fillable = ['kunci', 'nilai'];

    /**
     * @return array<string, string>
     */
    public static function semua(): array
    {
        $tersimpan = static::query()->pluck('nilai', 'kunci');

        // Bawaan hanya untuk kunci yang belum pernah disimpan; isian yang sengaja dikosongkan admin dihormati.
        return collect(self::BAWAAN)
            ->map(fn (string $bawaan, string $kunci) => $tersimpan->has($kunci) ? (string) $tersimpan[$kunci] : $bawaan)
            ->all();
    }

    /**
     * @param  array<string, ?string>  $nilai
     */
    public static function simpan(array $nilai): void
    {
        foreach (array_intersect_key($nilai, self::BAWAAN) as $kunci => $isi) {
            static::query()->updateOrCreate(['kunci' => $kunci], ['nilai' => $isi]);
        }
    }
}
