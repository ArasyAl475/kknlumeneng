<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EwsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun admin (password dari .env)
        if (config('ews.admin_password')) {
            User::updateOrCreate(
                ['email' => config('ews.admin_email')],
                ['name' => 'Admin EWS', 'password' => Hash::make(config('ews.admin_password'))]
            );
        } else {
            $this->command->warn('ADMIN_PASSWORD di .env masih kosong, akun admin TIDAK dibuat.');
        }

        // 2. Ambang batas awal (nanti diubah admin lewat halaman admin)
        foreach (['batas_waspada', 'batas_validasi', 'batas_hujan_waspada', 'batas_hujan_validasi', 'batas_miring_waspada', 'batas_miring_validasi'] as $kunci) {
            Pengaturan::firstOrCreate(['kunci' => $kunci], ['nilai' => (string) config('ews.' . $kunci)]);
        }

        // 3. Wilayah contoh
        foreach (config('ews.wilayah_awal') as $nama => $poligon) {
            Wilayah::firstOrCreate(['nama' => $nama], ['poligon' => $poligon]);
        }
    }
}
