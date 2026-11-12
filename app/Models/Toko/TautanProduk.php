<?php

namespace App\Models\Toko;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TautanProduk extends Model
{
    public const SUMBER_MANUAL = 'manual';

    public const SUMBER_API = 'api';

    protected $table = 'toko_tautan_produk';

    protected $fillable = ['produk_id', 'marketplace', 'url', 'id_eksternal', 'sumber', 'terakhir_sinkron'];

    protected function casts(): array
    {
        return ['terakhir_sinkron' => 'datetime'];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
