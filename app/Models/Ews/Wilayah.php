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

    protected $fillable = ['nama', 'poligon', 'status_awas', 'awas_sumber', 'awas_sejak'];

    protected function casts(): array
    {
        return [
            'poligon' => 'array',
            'status_awas' => 'boolean',
            'awas_sejak' => 'datetime',
        ];
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
