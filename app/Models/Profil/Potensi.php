<?php

namespace App\Models\Profil;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu potensi desa: daerahnya, penghasilnya, letaknya, dan fotonya. */
class Potensi extends Model
{
    use HasFactory;

    protected $table = 'profil_potensi';

    /**
     * Label + ikon (nama ikon di components/ikon-sprite). Ikon dipakai bersama kata,
     * jadi kategori tidak pernah dibedakan hanya lewat warna atau gambar.
     *
     * @var array<string, array{label: string, ikon: string}>
     */
    public const KATEGORI = [
        'hutan' => ['label' => 'Hutan', 'ikon' => 'daun'],
        'perkebunan' => ['label' => 'Kebun', 'ikon' => 'daun'],
        'pertanian' => ['label' => 'Sawah & ladang', 'ikon' => 'tanah'],
        'air' => ['label' => 'Air & sungai', 'ikon' => 'hujan'],
        'wisata' => ['label' => 'Wisata', 'ikon' => 'peta'],
        'lainnya' => ['label' => 'Lainnya', 'ikon' => 'desa'],
    ];

    protected $fillable = ['nama', 'kategori', 'hasil', 'lokasi', 'luas', 'keterangan', 'urutan'];

    protected function casts(): array
    {
        return ['urutan' => 'integer'];
    }

    public function scopeUrut(Builder $query): void
    {
        $query->orderBy('urutan')->orderBy('id');
    }

    public function labelKategori(): string
    {
        return self::KATEGORI[$this->kategori]['label'] ?? 'Lainnya';
    }

    public function ikonKategori(): string
    {
        return self::KATEGORI[$this->kategori]['ikon'] ?? 'desa';
    }

    /**
     * Foto potensi disimpan di galeri (tabel profil_galeri), sehingga otomatis tampil juga di halaman Galeri.
     * Urutan foto menentukan letaknya di kolase; foto pertama paling besar.
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(Foto::class, 'potensi_id')->orderBy('urutan')->orderBy('id');
    }

    // Foto pertama, untuk gambar kecil di daftar admin. Muat relasi fotos lebih dulu agar tidak ada kueri berulang.
    public function urlFotoUtama(): ?string
    {
        return $this->fotos->first()?->urlGambar();
    }

    /**
     * Admin menulis penghasil dipisah koma atau baris baru; di situs tampil sebagai label-label kecil.
     *
     * @return array<int, string>
     */
    public function daftarHasil(): array
    {
        return collect(preg_split('/[,\n;]+/u', (string) $this->hasil) ?: [])
            ->map(fn (string $h) => trim($h))
            ->filter()
            ->values()
            ->all();
    }
}
