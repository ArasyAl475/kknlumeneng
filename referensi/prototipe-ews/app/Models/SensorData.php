<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorData extends Model
{
    protected $table = 'sensor_data';

    // kelembapan = kelembapan TANAH (nama kolom lama dipertahankan supaya data lama tetap aman)
    protected $fillable = [
        'lokasi', 'kelembapan', 'kemiringan', 'curah_hujan', 'kelembapan_udara',
        'status', 'warna', 'pemicu', 'kabel_putus', 'validasi', 'divalidasi_pada',
    ];

    protected $casts = [
        'kabel_putus'      => 'boolean',
        'kemiringan'       => 'float',
        'curah_hujan'      => 'float',
        'kelembapan_udara' => 'float',
    ];
}
