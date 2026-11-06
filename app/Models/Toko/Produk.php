<?php

namespace App\Models\Toko;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produk extends Model
{
    use HasFactory, SoftDeletes;

    // Lima foto cukup untuk galeri ala marketplace tanpa membuat halaman berat di sinyal lemah.
    public const MAKS_FOTO = 5;

    protected $table = 'toko_produk';

    protected $fillable = ['nama', 'slug', 'deskripsi', 'harga', 'tersedia', 'tampil', 'urutan', 'spesifikasi'];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'tersedia' => 'boolean',
            'tampil' => 'boolean',
            'urutan' => 'integer',
            'spesifikasi' => 'array',
        ];
    }

    public function foto(): HasMany
    {
        return $this->hasMany(FotoProduk::class, 'produk_id')->orderBy('urutan')->orderBy('id');
    }

    public function varian(): HasMany
    {
        return $this->hasMany(VarianProduk::class, 'produk_id')->orderBy('urutan')->orderBy('id');
    }

    public function tautan(): HasMany
    {
        return $this->hasMany(TautanProduk::class, 'produk_id');
    }

    public function scopeTampil(Builder $query): void
    {
        $query->where('tampil', true)->orderBy('urutan')->orderBy('id');
    }

    public function tautanUntuk(string $marketplace): ?string
    {
        return $this->tautan->firstWhere('marketplace', $marketplace)?->url;
    }

    /**
     * Varian yang punya harga menentukan rentang; kalau tidak ada, pakai harga produk.
     *
     * @return array{0: ?int, 1: ?int}
     */
    public function rentangHarga(): array
    {
        $hargaVarian = $this->varian->pluck('harga')->filter(fn ($h) => $h !== null);

        if ($hargaVarian->isNotEmpty()) {
            return [$hargaVarian->min(), $hargaVarian->max()];
        }

        return [$this->harga, $this->harga];
    }

    public function hargaTeks(): ?string
    {
        [$min, $maks] = $this->rentangHarga();

        if ($min === null) {
            return null;
        }

        return $min === $maks ? self::rupiah($min) : self::rupiah($min).' – '.self::rupiah($maks);
    }

    // Produk habis kalau ditandai habis, atau kalau punya varian tetapi semuanya habis.
    public function adaStok(): bool
    {
        if (! $this->tersedia) {
            return false;
        }

        return $this->varian->isEmpty() || $this->varian->contains('tersedia', true);
    }

    public static function rupiah(int $nilai): string
    {
        return 'Rp '.number_format($nilai, 0, ',', '.');
    }
}
