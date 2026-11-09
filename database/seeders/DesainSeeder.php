<?php

namespace Database\Seeders;

use App\Models\Desain\SlotGambar;
use App\Models\Profil\Pengaturan;
use App\Services\Desain\DaftarSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DesainSeeder extends Seeder
{
    /**
     * Mengisi semua slot dengan gambar bawaan dari public/images/default. Aman dijalankan
     * berulang: gambar unggahan admin dan teks alt pilihannya tidak ditimpa.
     */
    public function run(): void
    {
        DaftarSlot::sinkronkan();
        $this->pindahkanFotoProfilLama();
    }

    /**
     * Dulu foto sampul dan foto kepala desa diunggah lewat menu Identitas Profil Desa.
     * Foto yang sudah ada dipindah ke slotnya agar tidak hilang dari situs setelah pindah menu.
     */
    private function pindahkanFotoProfilLama(): void
    {
        if (! Schema::hasTable('profil_pengaturan')) {
            return;
        }

        $profil = Pengaturan::semua();

        foreach (['profil.hero' => 'foto_sampul', 'profil.kepala-desa' => 'foto_kepala_desa'] as $kunci => $kolom) {
            if (filled($profil[$kolom] ?? null)) {
                SlotGambar::query()->where('kunci', $kunci)->whereNull('path')->update(['path' => $profil[$kolom]]);
            }
        }
    }
}
