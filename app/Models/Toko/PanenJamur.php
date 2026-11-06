<?php

namespace App\Models\Toko;

use Illuminate\Database\Eloquent\Model;

class PanenJamur extends Model
{
    protected $table = 'toko_panen_jamur';

    protected $fillable = ['tanggal_panen', 'jumlah_kg', 'jumlah_baglog', 'biaya_baglog_rupiah', 'dengan_cangkang_kopi'];

    protected function casts(): array
    {
        return [
            'tanggal_panen' => 'date',
            'jumlah_kg' => 'decimal:2',
            'jumlah_baglog' => 'integer',
            'biaya_baglog_rupiah' => 'integer',
            'dengan_cangkang_kopi' => 'boolean',
        ];
    }
}
