<?php

namespace App\Models\Desain;

use Illuminate\Database\Eloquent\Model;

class SlotGambar extends Model
{
    protected $table = 'desain_slot_gambar';

    protected $fillable = [
        'kunci', 'nama', 'halaman', 'urutan', 'penjelasan', 'ukuran_saran', 'rasio', 'gambar_bawaan', 'path', 'alt',
    ];

    protected function casts(): array
    {
        return ['urutan' => 'integer'];
    }

    public function pakaiBawaan(): bool
    {
        return blank($this->path);
    }
}
