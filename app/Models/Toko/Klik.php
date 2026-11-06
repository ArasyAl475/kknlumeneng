<?php

namespace App\Models\Toko;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Klik extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'toko_klik';

    protected $fillable = ['produk_id', 'marketplace'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /**
     * @return array<string, int> jumlah klik per kunci marketplace sejak waktu tertentu
     */
    public static function jumlahSejak(Carbon $sejak): array
    {
        return static::query()
            ->where('created_at', '>=', $sejak)
            ->selectRaw('marketplace, count(*) as jumlah')
            ->groupBy('marketplace')
            ->pluck('jumlah', 'marketplace')
            ->map(fn ($n) => (int) $n)
            ->all();
    }
}
