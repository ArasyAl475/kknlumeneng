<?php

namespace Tests\Feature\Fondasi;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutentikasiAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_dialihkan_ke_halaman_masuk(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_halaman_masuk_tampil(): void
    {
        $this->get('/masuk')->assertOk()->assertSee('Masuk admin');
    }

    public function test_admin_berhasil_masuk(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'sandi-rahasia-123']);

        $this->post('/masuk', ['email' => $admin->email, 'password' => 'sandi-rahasia-123'])
            ->assertRedirect(route('admin.beranda'));

        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk()->assertSee('Beranda admin');
    }

    public function test_sandi_salah_ditolak(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'sandi-rahasia-123']);

        $this->from('/masuk')->post('/masuk', ['email' => $admin->email, 'password' => 'salah'])
            ->assertRedirect('/masuk')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_akun_bukan_admin_tidak_bisa_masuk(): void
    {
        $warga = User::factory()->create(['password' => 'sandi-rahasia-123']);

        $this->from('/masuk')->post('/masuk', ['email' => $warga->email, 'password' => 'sandi-rahasia-123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_bukan_admin_ditolak_403_dengan_halaman_indonesia(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden()
            ->assertSee('Anda tidak punya akses');
    }

    public function test_percobaan_masuk_dibatasi(): void
    {
        foreach (range(1, 5) as $percobaan) {
            $this->post('/masuk', ['email' => 'a@contoh.test', 'password' => 'salah']);
        }

        $this->post('/masuk', ['email' => 'a@contoh.test', 'password' => 'salah'])->assertStatus(429);
    }

    public function test_keluar_mengakhiri_sesi(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/keluar')
            ->assertRedirect(route('landing'));

        $this->assertGuest();
    }

    public function test_is_admin_tidak_bisa_diisi_massal(): void
    {
        $this->assertFalse((new User)->isFillable('is_admin'));
    }
}
