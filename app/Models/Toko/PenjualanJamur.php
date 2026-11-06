<?php

namespace App\Models\Toko;

use Illuminate\Database\Eloquent\Model;

class PenjualanJamur extends Model
{
    protected $table = 'toko_penjualan_jamur';

    protected $fillable = ['tanggal_penjualan', 'jumlah_kg', 'dengan_cangkang_kopi', 'total_rupiah'];

    protected function casts(): array
    {
        return [
            'tanggal_penjualan' => 'date',
            'jumlah_kg' => 'decimal:2',
            'dengan_cangkang_kopi' => 'boolean',
            'total_rupiah' => 'integer',
        ];
    }
}
