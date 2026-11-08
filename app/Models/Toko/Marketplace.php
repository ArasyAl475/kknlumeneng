<?php

namespace App\Models\Toko;

use App\Services\Toko\DaftarMarketplace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Marketplace extends Model
{
    public $incrementing = false;

    protected $table = 'toko_marketplace';

    protected $primaryKey = 'kunci';

    protected $keyType = 'string';

    protected $fillable = ['kunci', 'url_toko', 'url_dasbor', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    /**
     * Tiga marketplace dalam urutan tetap. Baris yang belum ada diisi bawaan (aktif, tanpa tautan)
     * supaya halaman tetap jalan walau seeder belum dijalankan.
     *
     * @return Collection<string, self>
     */
    public static function semuaTerindeks(): Collection
    {
        $tersimpan = static::query()->get()->keyBy('kunci');

        return collect(DaftarMarketplace::KUNCI)->mapWithKeys(
            fn (string $kunci) => [$kunci => $tersimpan[$kunci] ?? new static(['kunci' => $kunci, 'aktif' => true])]
        );
    }

    public static function untuk(string $kunci): self
    {
        return static::semuaTerindeks()[$kunci];
    }
}
