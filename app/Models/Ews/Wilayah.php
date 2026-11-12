<?php

namespace App\Models\Ews;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wilayah extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ews_wilayah';

    protected $fillable = ['id_alat', 'nama', 'poligon', 'lintang', 'bujur', 'status_awas', 'awas_sumber', 'awas_sejak'];

    protected function casts(): array
    {
        return [
            'id_alat' => 'integer',
            'poligon' => 'array',
            'lintang' => 'float',
            'bujur' => 'float',
            'status_awas' => 'boolean',
            'awas_sejak' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Wilayah baru langsung bisa dipakai alat: id_alat diisi otomatis (sama dengan id baris bila masih bebas).
        static::created(function (Wilayah $wilayah): void {
            if ($wilayah->id_alat === null) {
                $wilayah->forceFill(['id_alat' => self::idAlatBebas($wilayah->id)])->saveQuietly();
            }
        });

        // Wilayah yang dihapus melepas ID alatnya agar bisa dipakai wilayah lain; alat dengan ID itu ditolak.
        static::deleting(function (Wilayah $wilayah): void {
            if ($wilayah->id_alat !== null) {
                static::withTrashed()->whereKey($wilayah->id)->update(['id_alat' => null]);
                $wilayah->id_alat = null;
                $wilayah->syncOriginalAttribute('id_alat');
            }
        });

        // Saat dipulihkan, angka lamanya mungkin sudah dipakai wilayah lain; ambil angka bebas terkecil.
        static::restoring(function (Wilayah $wilayah): void {
            $wilayah->id_alat = self::idAlatBebas();
        });
    }

    /**
     * ID alat bebas: angka yang disukai bila belum dipakai wilayah lain, jika tidak angka bebas terkecil mulai dari 1.
     */
    public static function idAlatBebas(?int $disukai = null): int
    {
        $dipakai = array_flip(static::withTrashed()->whereNotNull('id_alat')->pluck('id_alat')->all());

        if ($disukai !== null && $disukai > 0 && ! isset($dipakai[$disukai])) {
            return $disukai;
        }

        $angka = 1;
        while (isset($dipakai[$angka])) {
            $angka++;
        }

        return $angka;
    }

    public function pembacaan(): HasMany
    {
        return $this->hasMany(Pembacaan::class, 'wilayah_id');
    }

    // Satu baris terbaru per wilayah dengan satu query, tanpa N+1 saat di-eager-load.
    public function pembacaanTerbaru(): HasOne
    {
        return $this->hasOne(Pembacaan::class, 'wilayah_id')->latestOfMany('id');
    }
}
