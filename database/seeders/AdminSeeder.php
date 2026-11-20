<?php

namespace Database\Seeders;

use App\Models\User;
use App\Rules\SandiKuat;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.email');
        $sandi = (string) config('admin.password');

        if (! $email || $sandi === '') {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD di .env kosong: akun admin tidak dibuat.');

            return;
        }

        // Di lokal sandi lemah hanya diperingatkan agar percobaan tetap mudah;
        // di server hal ini harus menghentikan proses, bukan sekadar peringatan yang terlewat.
        if (! self::sandiKuat($sandi)) {
            if (! app()->environment(['local', 'testing'])) {
                throw new RuntimeException('ADMIN_PASSWORD terlalu lemah: minimal 8 karakter, berisi huruf dan angka, tanpa kata umum.');
            }

            $this->command?->warn('ADMIN_PASSWORD lemah. Di server, kata sandi seperti ini akan ditolak.');
        }

        $admin = User::query()->firstOrNew(['email' => $email]);

        // Sandi hanya dipasang saat akun dibuat. Seed boleh dijalankan ulang kapan saja (docs/DEPLOY.md),
        // jadi sandi yang sudah diganti admin lewat web tidak boleh kembali ke ADMIN_PASSWORD di .env.
        if (! $admin->exists) {
            $admin->fill(['name' => 'Admin', 'password' => $sandi]);
        }

        $admin->forceFill(['is_admin' => true])->save();
    }

    public static function sandiKuat(string $sandi): bool
    {
        return SandiKuat::memenuhi($sandi);
    }
}
