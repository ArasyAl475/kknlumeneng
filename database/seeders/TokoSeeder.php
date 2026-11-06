<?php

namespace Database\Seeders;

use App\Models\Toko\Marketplace;
use App\Models\Toko\Pengaturan;
use App\Models\Toko\Produk;
use App\Services\Toko\DaftarMarketplace;
use Illuminate\Database\Seeder;

class TokoSeeder extends Seeder
{
    /**
     * Hanya data yang sudah pasti. Harga, foto, varian, dan tautan diisi admin karena belum diketahui;
     * mengarangnya berisiko menampilkan informasi yang keliru ke pembeli.
     */
    private const PRODUK_AWAL = [
        ['slug' => 'kopi-bubuk', 'nama' => 'Kopi Bubuk', 'deskripsi' => 'Kopi bubuk kemasan dari Desa Lumeneng.'],
        ['slug' => 'baglog-jamur-tiram', 'nama' => 'Baglog Jamur Tiram', 'deskripsi' => 'Media tanam jamur tiram dari limbah kulit kopi, dikemas dalam plastik.'],
        ['slug' => 'bakso-jamur', 'nama' => 'Bakso Jamur', 'deskripsi' => 'Bakso olahan jamur tiram dari Desa Lumeneng.'],
        ['slug' => 'steak-jamur', 'nama' => 'Steak Jamur', 'deskripsi' => 'Steak olahan jamur tiram dari Desa Lumeneng.'],
        ['slug' => 'abon-jamur', 'nama' => 'Abon Jamur', 'deskripsi' => 'Abon olahan jamur tiram dari Desa Lumeneng.'],
    ];

    public function run(): void
    {
        // firstOrCreate: menjalankan ulang seeder tidak boleh menimpa isian admin.
        foreach (DaftarMarketplace::KUNCI as $kunci) {
            Marketplace::query()->firstOrCreate(['kunci' => $kunci], ['aktif' => true]);
        }

        foreach (Pengaturan::BAWAAN as $kunci => $nilai) {
            Pengaturan::query()->firstOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        }

        foreach (self::PRODUK_AWAL as $urutan => $produk) {
            Produk::withTrashed()->firstOrCreate(
                ['slug' => $produk['slug']],
                ['nama' => $produk['nama'], 'deskripsi' => $produk['deskripsi'], 'urutan' => $urutan + 1, 'tersedia' => true, 'tampil' => true],
            );
        }
    }
}
