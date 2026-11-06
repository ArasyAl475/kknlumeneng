<?php

namespace App\Models\Edukasi;

use App\Services\Edukasi\PengolahGambar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materi extends Model
{
    use HasFactory;

    protected $table = 'edukasi_materi';

    protected $fillable = [
        'kategori_id', 'judul', 'slug', 'ringkasan', 'penjelasan', 'gambar', 'video_youtube', 'diterbitkan_pada',
    ];

    protected function casts(): array
    {
        return ['diterbitkan_pada' => 'datetime'];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function langkah(): HasMany
    {
        return $this->hasMany(Langkah::class, 'materi_id')->orderBy('urutan')->orderBy('id');
    }

    public function scopeTerbit(Builder $query): void
    {
        $query->whereNotNull('diterbitkan_pada');
    }

    public function scopeCari(Builder $query, ?string $kata): void
    {
        // Nama kategori ikut dicari supaya warga yang mengetik "jamur" langsung menemukan semua materi kategori itu.
        $query->when(filled($kata), fn (Builder $q) => $q->where(
            fn (Builder $cocok) => $cocok
                ->whereAny(['judul', 'ringkasan', 'penjelasan'], 'like', "%{$kata}%")
                // Nama kategori hanya cocok bila ada kata yang diawali kata cari, agar "ag" tidak menemukan "Baglog".
                ->orWhereHas('kategori', fn (Builder $k) => $k->where('nama', 'like', "{$kata}%")->orWhere('nama', 'like', "% {$kata}%"))
        ));
    }

    public function scopeDalamKategori(Builder $query, ?string $slug): void
    {
        $query->when(filled($slug), fn (Builder $q) => $q->whereRelation('kategori', 'slug', $slug));
    }

    public function sudahTerbit(): bool
    {
        return $this->diterbitkan_pada !== null;
    }

    // Halaman yang hanya berisi judul dan ringkasan belum berguna bagi warga.
    public function siapTerbit(): bool
    {
        return filled($this->penjelasan) || $this->langkah()->exists();
    }

    public function urlGambar(): ?string
    {
        return PengolahGambar::url($this->gambar);
    }

    // Gambar utama dulu; bila tidak ada, pakai gambar sampul video agar kartu tidak kosong.
    public function urlThumbnail(): ?string
    {
        if ($this->urlGambar()) {
            return $this->urlGambar();
        }

        return $this->adaVideo() ? "https://i.ytimg.com/vi/{$this->video_youtube}/mqdefault.jpg" : null;
    }

    public function adaVideo(): bool
    {
        return filled($this->video_youtube);
    }
}
