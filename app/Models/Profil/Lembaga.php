<?php

namespace App\Models\Profil;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Lembaga extends Model
{
    protected $table = 'profil_lembaga';

    protected $fillable = ['nama', 'singkatan', 'ketua', 'keterangan', 'urutan'];

    protected function casts(): array
    {
        return ['urutan' => 'integer'];
    }

    public function scopeUrut(Builder $query): void
    {
        $query->orderBy('urutan')->orderBy('id');
    }

    // Tulisan di lingkaran kartu; lembaga tanpa singkatan memakai dua huruf awal namanya.
    public function sebutan(): string
    {
        return $this->singkatan ?: mb_strtoupper(mb_substr($this->nama, 0, 2));
    }
}
