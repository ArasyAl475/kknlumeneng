<?php

namespace Tests\Feature\Fondasi;

use App\Support\Modul;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StrukturTest extends TestCase
{
    use RefreshDatabase;

    public function test_beranda_tampil_dengan_disclaimer(): void
    {
        $this->get('/')->assertOk()->assertSee('bukan peringatan resmi BPBD');
    }

    public function test_halaman_404_berbahasa_indonesia(): void
    {
        $this->get('/tidak-ada')->assertNotFound()->assertSee('Halaman tidak ditemukan');
    }

    public function test_modul_mati_tidak_tersedia(): void
    {
        config(['modul.toko.aktif' => false]);

        $this->assertFalse(Modul::semua()['toko']['tersedia']);
    }

    public function test_modul_tanpa_rute_tidak_menjadi_tautan_mati(): void
    {
        config(['modul.toko.aktif' => true, 'modul.toko.rute' => 'rute.yang.belum.ada']);

        $this->assertFalse(Modul::semua()['toko']['tersedia']);
    }

    public function test_semua_rute_admin_terlindungi(): void
    {
        $ruteAdmin = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($rute) => str_starts_with($rute->uri(), 'admin'));

        $this->assertNotEmpty($ruteAdmin);

        foreach ($ruteAdmin as $rute) {
            $middleware = $rute->gatherMiddleware();
            $this->assertContains('auth', $middleware, $rute->uri());
            $this->assertContains('admin', $middleware, $rute->uri());
        }
    }
}
