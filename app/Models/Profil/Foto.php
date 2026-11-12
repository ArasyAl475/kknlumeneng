<?php

namespace App\Models\Profil;

use App\Services\Profil\PenyimpanGambar;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu foto galeri. Bisa terhubung ke berita/kegiatan atau berdiri sendiri (foto desa).
 * Judul, keterangan, dan tanggal yang kosong diambil dari berita yang terhubung.
 */
class Foto extends Model
{
    use HasFactory;

    public const JENIS = [
        'kegiatan' => 'Dokumentasi kegiatan',
        'desa' => 'Foto desa',
    ];

    protected $table = 'profil_galeri';

    protected $fillable = ['berita_id', 'potensi_id', 'gambar', 'judul', 'keterangan', 'tanggal', 'urutan'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'urutan' => 'integer'];
    }

    public function berita(): BelongsTo
    {
        return $this->belongsTo(Berita::class, 'berita_id');
    }

    // Foto yang diunggah lewat menu Potensi Desa; otomatis ikut tampil di Galeri.
    public function potensi(): BelongsTo
    {
        return $this->belongsTo(Potensi::class, 'potensi_id');
    }

    /**
     * Foto yang boleh dilihat warga: foto mandiri, atau foto dari berita yang sudah terbit.
     * Foto dari berita draf ikut tersembunyi agar isi kegiatan tidak bocor sebelum diumumkan.
     */
    public function scopePublik(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('berita_id')->orWhereHas('berita', fn (Builder $b) => $b->terbit()));
    }

    public function scopeJenis(Builder $query, ?string $jenis): void
    {
        $query->when($jenis === 'kegiatan', fn (Builder $q) => $q->whereNotNull('berita_id'))
            ->when($jenis === 'desa', fn (Builder $q) => $q->whereNull('berita_id'));
    }

    public function scopeTerbaru(Builder $query): void
    {
        $query->latest('created_at')->latest('id');
    }

    public function scopeUrut(Builder $query): void
    {
        $query->orderBy('urutan')->orderBy('id');
    }

    public function terhubung(): bool
    {
        return $this->berita_id !== null;
    }

    public function judulTampil(): string
    {
        return $this->judul ?: ($this->berita?->judul ?? 'Foto Desa Lumeneng');
    }

    public function keteranganTampil(): ?string
    {
        return $this->keterangan ?: $this->berita?->ringkasan;
    }

    public function tanggalTampil(): ?CarbonInterface
    {
        return $this->tanggal ?? $this->berita?->diterbitkan_pada ?? $this->created_at;
    }

    public function urlGambar(): ?string
    {
        return PenyimpanGambar::url($this->gambar);
    }
}
