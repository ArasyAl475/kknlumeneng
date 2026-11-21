<?php

namespace Tests\Unit\Toko;

use App\Models\Toko\Produk;
use Tests\TestCase;

class SpesifikasiProdukTest extends TestCase
{
    public function test_judul_bebas_tampil_sesuai_urutan_admin(): void
    {
        $produk = new Produk(['tersedia' => true, 'spesifikasi' => [
            ['label' => 'Berat Bersih', 'nilai' => '250 g'],
            ['label' => 'Cara Seduh', 'nilai' => 'Tubruk atau V60'],
            ['label' => 'Merek', 'nilai' => 'Kopi Lumeneng'],
        ]]);

        $this->assertSame(
            ['Stok', 'Berat Bersih', 'Cara Seduh', 'Merek', 'Dikirim Dari'],
            array_column($produk->spesifikasiPublik('Pekalongan'), 'label'),
        );
    }

    public function test_kategori_selalu_di_atas_lalu_stok(): void
    {
        // Kategori sengaja ditaruh di akhir dan ditulis huruf kecil untuk membuktikan posisinya ditentukan kode.
        $produk = new Produk(['tersedia' => true, 'spesifikasi' => [
            ['label' => 'Merek', 'nilai' => 'M'],
            ['label' => 'Berat', 'nilai' => '250 g'],
            ['label' => 'kategori', 'nilai' => 'K'],
        ]]);

        $this->assertSame([
            ['label' => 'kategori', 'nilai' => 'K'],
            ['label' => 'Stok', 'nilai' => 'Tersedia'],
            ['label' => 'Merek', 'nilai' => 'M'],
            ['label' => 'Berat', 'nilai' => '250 g'],
            ['label' => 'Dikirim Dari', 'nilai' => 'Pekalongan'],
        ], $produk->spesifikasiPublik('Pekalongan'));
    }

    public function test_baris_kosong_dan_baris_otomatis_lama_dibuang(): void
    {
        $produk = new Produk(['tersedia' => false, 'spesifikasi' => [
            ['label' => 'Merek', 'nilai' => ''],
            ['label' => '', 'nilai' => 'tanpa judul'],
            ['label' => ' stok ', 'nilai' => 'Banyak'],
            ['label' => 'DIKIRIM DARI', 'nilai' => 'Jakarta'],
            ['label' => ' Berat ', 'nilai' => ' 250 g '],
        ]]);

        $this->assertSame([['label' => 'Berat', 'nilai' => '250 g']], $produk->spesifikasiIsian());
        $this->assertSame([
            ['label' => 'Stok', 'nilai' => 'Habis'],
            ['label' => 'Berat', 'nilai' => '250 g'],
        ], $produk->spesifikasiPublik(null));
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

    public function test_label_otomatis_dikenali_tanpa_membedakan_huruf_dan_spasi(): void
    {
        $this->assertTrue(Produk::labelOtomatis('stok'));
        $this->assertTrue(Produk::labelOtomatis('  Dikirim dari '));
        $this->assertFalse(Produk::labelOtomatis('Stok Gudang'));
    }
}
