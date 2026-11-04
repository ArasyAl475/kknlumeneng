<?php

namespace Database\Seeders;

use App\Models\User;
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
                throw new RuntimeException('ADMIN_PASSWORD terlalu lemah: minimal 12 karakter, berisi huruf dan angka, tanpa kata umum.');
            }

            $this->command?->warn('ADMIN_PASSWORD lemah. Di server, kata sandi seperti ini akan ditolak.');
        }

        User::query()->updateOrCreate(['email' => $email], ['name' => 'Admin', 'password' => $sandi])
            ->forceFill(['is_admin' => true])
            ->save();
    }

    public static function sandiKuat(string $sandi): bool
    {
        return mb_strlen($sandi) >= 12
            && preg_match('/\pL/u', $sandi) === 1
            && preg_match('/\d/', $sandi) === 1
            && preg_match('/password|sandi|admin|lumeneng|qwerty|12345/i', $sandi) !== 1;
    }
}
