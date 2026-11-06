<?php

namespace Tests\Feature\Toko;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_warga_dapat_melihat_katalog_produk_sederhana(): void
    {
        $response = $this->get('/toko');

        $response->assertOk()
            ->assertSee('Koci Circular')
            ->assertSee('Katalog Produk Sederhana')
            ->assertSee('Jamur Tiram Putih')
            ->assertSee('Baglog Jamur Koci')
            ->assertSee('Kopi Eco-Wash')
            ->assertSee('Kompos Sirkular')
            ->assertSee('14.000')
            ->assertSee('6.500')
            ->assertSee('65.000')
            ->assertSee('25.000')
            ->assertSee(route('toko.hasil'));
    }

    public function test_katalog_memuat_filter_dan_kategori(): void
    {
        $response = $this->get('/toko');

        $response->assertOk()
            ->assertSee('Jamur Tiram &amp; Olahan', false)
            ->assertSee('Kopi Konservasi')
            ->assertSee('Grade A')
            ->assertSee('Kemasan 500g')
            ->assertSee('Baglog Siap Tumbuh')
            ->assertSee('Pupuk Kompos Koci');
    }

    public function test_rute_alias_katalog_juga_bisa_diakses(): void
    {
        $this->get('/toko/katalog')
            ->assertOk()
            ->assertSee('Katalog Produk Sederhana');
    }
}
