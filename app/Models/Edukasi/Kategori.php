<?php

namespace App\Models\Edukasi;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'edukasi_kategori';

    protected $fillable = ['nama', 'slug', 'tips_sirkular'];

    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class, 'kategori_id');
    }

    // Warga tidak perlu melihat pilihan kategori yang isinya kosong.
    public function scopePunyaMateriTerbit(Builder $query): void
    {
        $query->whereHas('materi', fn (Builder $materi) => $materi->terbit());
    }
}
