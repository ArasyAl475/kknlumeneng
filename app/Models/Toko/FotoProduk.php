<?php

namespace App\Models\Toko;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FotoProduk extends Model
{
    protected $table = 'toko_foto_produk';

    protected $fillable = ['produk_id', 'path', 'path_kecil', 'urutan'];

    protected function casts(): array
    {
        return ['urutan' => 'integer'];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function urlKecil(): string
    {
        return Storage::disk('public')->url($this->path_kecil);
    }
}
