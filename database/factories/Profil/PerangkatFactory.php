<?php

namespace Database\Factories\Profil;

use App\Models\Profil\Perangkat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Perangkat>
 */
class PerangkatFactory extends Factory
{
    protected $model = Perangkat::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'jabatan' => 'Kaur Umum',
            'kelompok' => 'kaur',
            'urutan' => 0,
        ];
    }
}
