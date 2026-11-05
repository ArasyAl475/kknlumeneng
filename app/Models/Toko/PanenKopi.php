<?php

namespace App\Models\Toko;

use Illuminate\Database\Eloquent\Model;

class PanenKopi extends Model
{
    protected $table = 'toko_panen_kopi';

    protected $fillable = ['tanggal_panen', 'jenis_kopi', 'tempat_panen', 'jumlah_kg', 'limbah_kg'];

    protected function casts(): array
    {
        return [
            'tanggal_panen' => 'date',
            'jumlah_kg' => 'decimal:2',
            'limbah_kg' => 'decimal:2',
        ];
    }
}
