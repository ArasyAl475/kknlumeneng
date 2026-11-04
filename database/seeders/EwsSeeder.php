<?php

namespace Database\Seeders;

use App\Models\Ews\Pengaturan;
use App\Models\Ews\Wilayah;
use Illuminate\Database\Seeder;

class EwsSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate: menjalankan ulang seeder tidak boleh menimpa ambang yang sudah diubah admin.
        foreach (config('ews.ambang_awal') as $sensor => $batas) {
            foreach ($batas as $tingkat => $nilai) {
                Pengaturan::query()->firstOrCreate(['kunci' => "{$sensor}_{$tingkat}"], ['nilai' => (string) $nilai]);
            }
        }

        foreach (config('ews.wilayah_awal') as $nama => $poligon) {
            Wilayah::withTrashed()->firstOrCreate(['nama' => $nama], ['poligon' => $poligon]);
        }
    }
}
