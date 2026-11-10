<?php

namespace App\Models\Profil;

use App\Services\Profil\IsiKaya;
use App\Services\Profil\PenyimpanGambar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;

class Berita extends Model
{
    use HasFactory;

    // Daftar tetap, agar admin tidak perlu mengelola kategori dan warga tidak bingung memilih saringan.
    public const JENIS = [
        'berita' => 'Berita',
        'pengumuman' => 'Pengumuman',
        'artikel' => 'Artikel',
    ];

    protected $table = 'profil_berita';

    protected $fillable = ['jenis', 'judul', 'slug', 'ringkasan', 'isi', 'gambar', 'penulis', 'diterbitkan_pada'];

    protected function casts(): array
    {
        return ['diterbitkan_pada' => 'datetime'];
    }

    public function scopeTerbit(Builder $query): void
    {
        $query->whereNotNull('diterbitkan_pada');
    }

    public function scopeTerbaru(Builder $query): void
    {
        $query->latest('diterbitkan_pada')->latest('id');
    }

    public function scopeJenis(Builder $query, ?string $jenis): void
    {
        $query->when(filled($jenis), fn (Builder $q) => $q->where('jenis', $jenis));
    }

    public function scopeCari(Builder $query, ?string $kata): void
    {
        $query->when(filled($kata), fn (Builder $q) => $q->whereAny(['judul', 'ringkasan', 'isi'], 'like', "%{$kata}%"));
    }

    public function sudahTerbit(): bool
    {
        return $this->diterbitkan_pada !== null;
    }

    public function labelJenis(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }

    public function urlGambar(): ?string
    {
        return PenyimpanGambar::url($this->gambar);
    }

    // Dokumentasi kegiatan: satu berita punya banyak foto galeri.
    public function foto(): HasMany
    {
        // chaperone: tiap foto langsung tahu beritanya tanpa kueri tambahan (judul/keterangan cadangan diambil dari sana).
        return $this->hasMany(Foto::class, 'berita_id')->chaperone('berita')->orderBy('urutan')->orderBy('id');
    }

    // Isi berformat yang sudah dibersihkan, siap ditampilkan (lihat IsiKaya).
    public function isiHtml(): HtmlString
    {
        return IsiKaya::tampilkan($this->isi);
    }

    // Perkiraan waktu baca membantu warga memutuskan membaca sekarang atau nanti.
    public function menitBaca(): int
    {
        $jumlahKata = count(preg_split('/\s+/u', IsiKaya::teks($this->isi), -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return max(1, (int) ceil($jumlahKata / 180));
    }
}
