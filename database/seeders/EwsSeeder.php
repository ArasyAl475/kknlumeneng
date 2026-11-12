<?php

namespace Database\Seeders;

use App\Models\Ews\Pengaturan;
use App\Models\Ews\Titik;
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

        foreach (config('ews.wilayah_awal') as $nama => $data) {
            Wilayah::withTrashed()->firstOrCreate(['nama' => $nama], [
                'poligon' => $data['poligon'],
                'lintang' => $data['lokasi_alat'][0],
                'bujur' => $data['lokasi_alat'][1],
            ]);
        }

        foreach (config('ews.titik_awal') as $nama => $data) {
            Titik::query()->firstOrCreate(['nama' => $nama], [
                'jenis' => $data['jenis'],
                'poligon' => $data['poligon'] ?? null,
                'lintang' => $data['lokasi_alat'][0],
                'bujur' => $data['lokasi_alat'][1],
            ]);
        }
    }
}
