<?php

namespace Tests\Feature\Fondasi;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class SeederAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_membuat_admin_dari_konfigurasi(): void
    {
        config(['admin.email' => 'admin@contoh.test', 'admin.password' => 'tebing-Hijau-7-rimba']);

        $this->seed(AdminSeeder::class);

        $admin = User::where('email', 'admin@contoh.test')->sole();
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check('tebing-Hijau-7-rimba', $admin->password));
    }

    public function test_tidak_membuat_akun_jika_konfigurasi_kosong(): void
    {
        config(['admin.email' => null, 'admin.password' => null]);

        $this->seed(AdminSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_sandi_lemah_ditolak_di_luar_lokal(): void
    {
        config(['admin.email' => 'admin@contoh.test', 'admin.password' => 'admin123']);
        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);

        (new AdminSeeder)->run();
    }
}
