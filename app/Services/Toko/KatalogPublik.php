<?php

namespace App\Services\Toko;

use App\Models\Toko\Marketplace;
use App\Models\Toko\Produk;
use Illuminate\Support\Collection;

/**
 * Query halaman publik. Tidak di-cache: katalog hanya beberapa produk dan cache proyek
 * sengaja menolak menyimpan objek PHP (cache.serializable_classes = false).
 */
final class KatalogPublik
{
    /**
     * @return Collection<int, Produk>
     */
    public static function semuaTampil(): Collection
    {
        return Produk::query()->tampil()->with(['foto', 'varian', 'tautan'])->get();
    }

    /**
     * @param  Collection<int, Produk>|null  $produk  dipakai ulang bila sudah dimuat, agar tidak query dua kali
     * @return Collection<int, Produk>
     */
    public static function produkDi(Marketplace $marketplace, ?Collection $produk = null): Collection
    {
        return ($produk ?? self::semuaTampil())
            ->filter(fn (Produk $p) => TautanBeli::untuk($p, $marketplace) !== null)
            ->values();
    }
}
