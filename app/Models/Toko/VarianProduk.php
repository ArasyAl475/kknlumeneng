<?php

namespace App\Models\Toko;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VarianProduk extends Model
{
    protected $table = 'toko_varian_produk';

    protected $fillable = ['produk_id', 'nama', 'harga', 'tersedia', 'urutan'];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'tersedia' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
