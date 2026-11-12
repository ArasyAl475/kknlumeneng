<?php

namespace Tests\Unit\Toko;

use App\Models\Toko\Produk;
use Tests\TestCase;

class SpesifikasiProdukTest extends TestCase
{
    public function test_label_dicocokkan_tanpa_membedakan_huruf_besar_kecil(): void
    {
        $produk = new Produk(['spesifikasi' => [
            ['label' => 'MEREK', 'nilai' => 'A'],
            ['label' => 'tipe paket', 'nilai' => 'B'],
        ]]);

        $this->assertSame(['merek' => 'A', 'tipe_paket' => 'B'], $produk->spesifikasiUtama());
    }

    public function test_label_kembar_memakai_yang_pertama(): void
    {
        $produk = new Produk(['spesifikasi' => [
            ['label' => 'Merek', 'nilai' => 'Pertama'],
            ['label' => 'merek', 'nilai' => 'Kedua'],
        ]]);

        $this->assertSame(['merek' => 'Pertama'], $produk->spesifikasiUtama());
    }

    public function test_baris_tambahan_lama_diabaikan(): void
    {
        $produk = new Produk(['tersedia' => true, 'spesifikasi' => [
            ['label' => 'Merek', 'nilai' => 'A'],
            ['label' => 'Berat', 'nilai' => '250 g'],
        ]]);

        $this->assertSame(['merek' => 'A'], $produk->spesifikasiUtama());
        $this->assertNotContains('Berat', array_column($produk->spesifikasiPublik(null), 'label'));
    }

    public function test_tanpa_spesifikasi_tetap_menghasilkan_baris_stok_dan_dikirim_dari(): void
    {
        $produk = new Produk(['tersedia' => true, 'spesifikasi' => null]);

        $this->assertSame([
            ['label' => 'Stok', 'nilai' => 'Tersedia'],
            ['label' => 'Dikirim Dari', 'nilai' => 'Pekalongan'],
        ], $produk->spesifikasiPublik('Pekalongan'));
    }

    public function test_baris_dikirim_dari_dibuang_bila_lokasi_kosong(): void
    {
        $produk = new Produk(['tersedia' => false]);

        $this->assertSame([['label' => 'Stok', 'nilai' => 'Habis']], $produk->spesifikasiPublik(null));
        $this->assertSame([['label' => 'Stok', 'nilai' => 'Habis']], $produk->spesifikasiPublik(''));
    }

    public function test_urutan_publik_kategori_stok_utama_lalu_dikirim_dari(): void
    {
        // Sengaja disimpan acak untuk membuktikan urutan tampil ditentukan kode, bukan urutan simpan.
        $produk = new Produk(['tersedia' => true, 'spesifikasi' => [
            ['label' => 'Spesies Tanaman', 'nilai' => 'Coffea arabica'],
            ['label' => 'Merek', 'nilai' => 'M'],
            ['label' => 'Kategori', 'nilai' => 'K'],
        ]]);

        $this->assertSame(
            ['Kategori', 'Stok', 'Merek', 'Spesies Tanaman', 'Dikirim Dari'],
            array_column($produk->spesifikasiPublik('Pekalongan'), 'label'),
        );
    }
}
