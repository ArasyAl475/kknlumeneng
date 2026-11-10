<?php

namespace App\Services\Toko;

use App\Models\Toko\Produk;
use App\Models\Toko\TautanProduk;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PenyimpanProduk
{
    /**
     * @param  array{nama: string, deskripsi: ?string, harga: ?int, tersedia: bool, tampil: bool, urutan: int,
     *     spesifikasi: array<int, array{label: string, nilai: string}>, tautan: array<string, ?string>,
     *     varian: array<int, array{nama: string, harga: ?int, tersedia: bool}>}  $data
     */
    public static function simpan(Produk $produk, array $data): Produk
    {
        DB::transaction(function () use ($produk, $data) {
            $produk->fill(Arr::only($data, ['nama', 'deskripsi', 'harga', 'tersedia', 'tampil', 'urutan']));
            $produk->spesifikasi = $data['spesifikasi'] ?: null;

            // Slug dibuat sekali saja: tautan produk yang sudah dibagikan di WhatsApp tidak boleh mati
            // hanya karena nama produk diperbaiki.
            if (! $produk->exists) {
                $produk->slug = self::slugUnik($data['nama']);
            }

            $produk->save();

            self::simpanTautan($produk, $data['tautan']);
            self::simpanVarian($produk, $data['varian']);
        });

        return $produk->load(['tautan', 'varian']);
    }

    /**
     * @param  array<string, ?string>  $tautan
     */
    private static function simpanTautan(Produk $produk, array $tautan): void
    {
        foreach (DaftarMarketplace::KUNCI as $kunci) {
            $url = trim((string) ($tautan[$kunci] ?? ''));

            if ($url === '') {
                $produk->tautan()->where('marketplace', $kunci)->delete();

                continue;
            }

            $produk->tautan()->updateOrCreate(
                ['marketplace' => $kunci],
                ['url' => $url, 'sumber' => TautanProduk::SUMBER_MANUAL],
            );
        }
    }

    /**
     * Varian tidak dirujuk tabel lain, jadi cukup diganti seluruhnya sesuai isian form.
     *
     * @param  array<int, array{nama: string, harga: ?int, tersedia: bool}>  $varian
     */
    private static function simpanVarian(Produk $produk, array $varian): void
    {
        $produk->varian()->delete();

        foreach (array_values($varian) as $urutan => $baris) {
            $produk->varian()->create($baris + ['urutan' => $urutan]);
        }
    }

    // Publik agar sinkron marketplace memakai aturan slug yang sama persis dengan form admin.
    public static function slugUnik(string $nama): string
    {
        $dasar = Str::slug($nama) ?: 'produk';
        $slug = $dasar;
        $nomor = 2;

        // Termasuk produk terhapus, karena produk itu bisa dipulihkan dengan slug lamanya.
        while (Produk::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $dasar.'-'.$nomor++;
        }

        return $slug;
    }
}
