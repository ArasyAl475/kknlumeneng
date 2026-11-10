<?php

namespace App\Models\Toko;

use Illuminate\Database\Eloquent\Model;

// Keadaan terakhir tiap baris yang sudah disinkronkan dengan Google Sheets.
class SinkronSheet extends Model
{
    protected $table = 'toko_sinkron_sheet';

    protected $fillable = ['jenis', 'catatan_id', 'hash'];
}
