<?php

namespace Database\Factories\Profil;

use App\Models\Profil\Potensi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Potensi>
 */
class PotensiFactory extends Factory
{
    protected $model = Potensi::class;

    public function definition(): array
    {
        return [
            'nama' => 'Potensi '.fake()->unique()->numberBetween(1, 99999),
            'kategori' => 'perkebunan',
            'hasil' => 'Kopi, durian, manggis',
            'lokasi' => 'Dusun Contoh',
            'luas' => '10 ha',
            'keterangan' => 'Keterangan singkat potensi.',
            'urutan' => 0,
        ];
    }
}
