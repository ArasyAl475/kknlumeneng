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
     * Isian spesifikasi yang disediakan sebagai baris siap pakai di form admin, urut seperti di Shopee.
     * Stok dan "Dikirim Dari" sengaja tidak ada di sini: keduanya terisi otomatis (status stok dan Info penjual).
     * Tersimpan tetap sebagai daftar {label, nilai} di kolom spesifikasi, jadi tidak perlu migrasi.
     */
    public const SPESIFIKASI_UTAMA = [
        'kategori' => 'Kategori',
        'merek' => 'Merek',
        'tipe_paket' => 'Tipe Paket',
        'asal_produk' => 'Asal Produk',
        'produk_custom' => 'Produk Custom',
        'jenis_biji_bijian' => 'Jenis Biji-Bijian',
        'spesies_tanaman' => 'Spesies Tanaman',
    ];

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

    /**
     * Memisahkan spesifikasi tersimpan menjadi isian utama (kunci => nilai) dan baris tambahan bebas.
     * Label dicocokkan tanpa membedakan huruf besar-kecil agar data lama yang diketik manual tetap terbaca.
     *
     * @return array{utama: array<string, string>, tambahan: array<int, array{label: string, nilai: string}>}
     */
    public function spesifikasiTerpisah(): array
    {
        $peta = collect(self::SPESIFIKASI_UTAMA)->mapWithKeys(fn (string $label, string $kunci) => [mb_strtolower($label) => $kunci]);
        $utama = [];
        $tambahan = [];

        foreach ($this->spesifikasi ?? [] as $baris) {
            $kunci = $peta[mb_strtolower($baris['label'])] ?? null;

            // Label kembar kedua dan seterusnya masuk ke tambahan agar tidak hilang diam-diam.
            if ($kunci !== null && ! isset($utama[$kunci])) {
                $utama[$kunci] = $baris['nilai'];
            } else {
                $tambahan[] = $baris;
            }
        }

        return ['utama' => $utama, 'tambahan' => $tambahan];
    }

    /**
     * Tabel "Spesifikasi Produk" di halaman publik, urut seperti Shopee: Kategori, Stok, isian lain,
     * baris tambahan, lalu Dikirim Dari. Stok dan Dikirim Dari selalu dihitung di sini, bukan diketik admin.
     *
     * @return array<int, array{label: string, nilai: string}>
     */
    public function spesifikasiPublik(?string $dikirimDari): array
    {
        ['utama' => $utama, 'tambahan' => $tambahan] = $this->spesifikasiTerpisah();
        $baris = [];

        if (isset($utama['kategori'])) {
            $baris[] = ['label' => self::SPESIFIKASI_UTAMA['kategori'], 'nilai' => $utama['kategori']];
        }

        $baris[] = ['label' => 'Stok', 'nilai' => $this->adaStok() ? 'Tersedia' : 'Habis'];

        foreach (self::SPESIFIKASI_UTAMA as $kunci => $label) {
            if ($kunci !== 'kategori' && isset($utama[$kunci])) {
                $baris[] = ['label' => $label, 'nilai' => $utama[$kunci]];
            }
        }

        array_push($baris, ...$tambahan);

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
