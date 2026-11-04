<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminSeeder::class);

        // Seeder modul dipanggil kalau berkasnya ada, agar modul baru cukup menambah
        // database/seeders/<Modul>Seeder.php tanpa mengedit berkas bersama ini.
        foreach (array_keys(config('modul', [])) as $modul) {
            $kelas = __NAMESPACE__.'\\'.ucfirst($modul).'Seeder';

            if (class_exists($kelas)) {
                $this->call($kelas);
            }
        }
    }
}
