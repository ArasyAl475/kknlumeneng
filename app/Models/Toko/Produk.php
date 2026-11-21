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

    /**
     * Judul spesifikasi yang disarankan (judul => contoh isi), urut seperti di Shopee. Ini hanya bantuan:
     * produk baru diberi baris berisi judul ini, dan admin bebas mengganti, menghapus, atau menambah judul
     * sendiri karena tiap produk desa berbeda (kopi punya jenis biji, jamur tidak).
     */
    public const SARAN_SPESIFIKASI = [
        'Kategori' => 'contoh: Makanan & Minuman',
        'Merek' => 'contoh: nama usaha, atau Tanpa merek',
        'Tipe Paket' => 'contoh: Kemasan 250 g',
        'Asal Produk' => 'contoh: Desa Lumeneng, Pekalongan',
        'Produk Custom' => 'contoh: Tidak',
        'Jenis Biji-Bijian' => 'contoh: Kopi Arabika (khusus produk kopi)',
        'Spesies Tanaman' => 'contoh: Coffea arabica (khusus produk kopi)',
    ];

    /**
     * Baris yang selalu dihitung sendiri dari status stok dan Info penjual, bukan diketik admin,
     * supaya tidak pernah bertentangan dengan keadaan sebenarnya.
     */
    public const LABEL_OTOMATIS = ['Stok', 'Dikirim Dari'];

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

    // Judul dibandingkan tanpa membedakan huruf besar-kecil dan spasi tepi: admin awam mengetik "stok " atau "KATEGORI".
    public static function samakanLabel(string $label): string
    {
        return mb_strtolower(trim($label));
    }

    public static function labelOtomatis(string $label): bool
    {
        return in_array(self::samakanLabel($label), array_map(self::samakanLabel(...), self::LABEL_OTOMATIS), true);
    }

    /**
     * Baris spesifikasi isian admin yang layak tampil, urut seperti yang disusun admin.
     * Baris kosong dan baris berjudul Stok / Dikirim Dari (data lama) dibuang agar tidak dobel dengan baris otomatis.
     *
     * @return array<int, array{label: string, nilai: string}>
     */
    public function spesifikasiIsian(): array
    {
        return collect($this->spesifikasi ?? [])
            ->map(fn ($b) => ['label' => trim((string) ($b['label'] ?? '')), 'nilai' => trim((string) ($b['nilai'] ?? ''))])
            ->filter(fn (array $b) => $b['label'] !== '' && $b['nilai'] !== '' && ! self::labelOtomatis($b['label']))
            ->values()->all();
    }

    /**
     * Tabel "Spesifikasi Produk" di halaman publik, mirip Shopee: Kategori (bila ada) di atas, lalu Stok,
     * baris lain sesuai urutan admin, dan Dikirim Dari di akhir.
     *
     * @return array<int, array{label: string, nilai: string}>
     */
    public function spesifikasiPublik(?string $dikirimDari): array
    {
        $isian = collect($this->spesifikasiIsian());
        $posisiKategori = $isian->search(fn (array $b) => self::samakanLabel($b['label']) === 'kategori');
        $baris = [];

        if ($posisiKategori !== false) {
            $baris[] = $isian->pull($posisiKategori);
        }

        $baris[] = ['label' => 'Stok', 'nilai' => $this->adaStok() ? 'Tersedia' : 'Habis'];
        array_push($baris, ...$isian->values()->all());

        if (filled($dikirimDari)) {
            $baris[] = ['label' => 'Dikirim Dari', 'nilai' => $dikirimDari];
        }

        return $baris;
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
