<?php

namespace App\Models\Toko;

use Illuminate\Database\Eloquent\Model;

/**
 * Izin akses ke satu toko Shopee. Token dienkripsi di basis data agar bocornya cadangan database
 * tidak langsung memberi akses ke toko; $hidden mencegahnya ikut tercetak saat model diubah ke JSON.
 */
class TokenShopee extends Model
{
    protected $table = 'toko_shopee_token';

    protected $fillable = [
        'shop_id', 'nama_toko', 'access_token', 'refresh_token',
        'akses_kedaluwarsa_pada', 'refresh_kedaluwarsa_pada', 'terhubung_oleh',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected function casts(): array
    {
        return [
            'shop_id' => 'integer',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'akses_kedaluwarsa_pada' => 'datetime',
            'refresh_kedaluwarsa_pada' => 'datetime',
        ];
    }

    /** Setelah refresh token habis, admin harus menekan "Hubungkan dengan Shopee" lagi. */
    public function masihBerlaku(): bool
    {
        return $this->refresh_kedaluwarsa_pada->isFuture();
    }
}
