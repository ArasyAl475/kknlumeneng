<?php

namespace App\Models\Edukasi;

use App\Services\Edukasi\PengolahGambar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Langkah extends Model
{
    use HasFactory;

    protected $table = 'edukasi_langkah';

    protected $fillable = ['materi_id', 'urutan', 'judul', 'isi', 'gambar'];

    // Tanggal "Diperbarui" di halaman warga harus ikut berubah saat langkahnya diubah.
    protected $touches = ['materi'];

    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class, 'materi_id');
    }

    public function urlGambar(): ?string
    {
        return PengolahGambar::url($this->gambar);
    }
}
