<?php

namespace Tests\Feature\Edukasi\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AksesAdminEdukasiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function semuaRuteEdukasi(): array
    {
        $daftar = [];

        foreach (Route::getRoutes()->getRoutes() as $rute) {
            if (str_starts_with($rute->uri(), 'admin/edukasi')) {
                foreach (array_diff($rute->methods(), ['HEAD']) as $metode) {
                    $daftar[] = [$metode, '/'.preg_replace('/\{[^}]+\}/', '1', $rute->uri())];
                }
            }
        }

        return $daftar;
    }

    public function test_daftar_rute_tidak_kosong(): void
    {
        $this->assertGreaterThan(15, count($this->semuaRuteEdukasi()));
    }

    public function test_tamu_dialihkan_dari_semua_rute(): void
    {
        foreach ($this->semuaRuteEdukasi() as [$metode, $uri]) {
            $this->withoutMiddleware(PreventRequestForgery::class)
                ->call($metode, $uri)->assertRedirect(route('login'));
        }
    }

    public function test_bukan_admin_ditolak_di_semua_rute(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ($this->semuaRuteEdukasi() as [$metode, $uri]) {
            $this->withoutMiddleware(PreventRequestForgery::class)
                ->call($metode, $uri)->assertForbidden();
        }
    }
}
