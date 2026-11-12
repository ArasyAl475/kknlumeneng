<?php

namespace App\Models\Profil;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    /**
     * Semua isian profil desa. Hanya identitas dasar yang punya nilai awal; isian lain sengaja
     * kosong agar bagian halaman yang belum diisi admin disembunyikan, bukan menampilkan data karangan.
     */
    public const BAWAAN = [
        'nama_desa' => 'Lumeneng',
        'tagline' => '',
        'kecamatan' => 'Paninggaran',
        'kabupaten' => 'Pekalongan',
        'provinsi' => 'Jawa Tengah',
        'foto_sampul' => '',
        'nama_kepala_desa' => '',
        'foto_kepala_desa' => '',
        'sambutan' => '',
        'sejarah' => '',
        'visi' => '',
        'misi' => '',
        'luas_wilayah' => '',
        'ketinggian' => '',
        'jumlah_penduduk' => '',
        'jumlah_kk' => '',
        'jumlah_dusun' => '',
        'jumlah_rw' => '',
        'jumlah_rt' => '',
        'batas_utara' => '',
        'batas_selatan' => '',
        'batas_timur' => '',
        'batas_barat' => '',
        'alamat_kantor' => '',
        'telepon' => '',
        'email' => '',
        'jam_pelayanan' => '',
        'tautan_peta' => '',
    ];

    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'profil_pengaturan';

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

    /**
     * Dipakai seeder: data contoh hanya mengisi kunci yang belum pernah disimpan, agar
     * menjalankan seeder ulang tidak menimpa tulisan admin.
     *
     * @param  array<string, string>  $nilai
     */
    public static function isiBilaKosong(array $nilai): void
    {
        $sudahAda = static::query()->pluck('kunci')->all();

        static::simpan(array_diff_key($nilai, array_flip($sudahAda)));
    }
}
