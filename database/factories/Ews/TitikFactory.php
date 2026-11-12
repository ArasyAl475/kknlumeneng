<?php

namespace Database\Factories\Ews;

use App\Models\Ews\Titik;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Titik>
 */
class TitikFactory extends Factory
{
    protected $model = Titik::class;

    public function definition(): array
    {
        return [
            'nama' => 'Titik '.fake()->unique()->numberBetween(1, 99999),
            'jenis' => Titik::JENIS_RAWAN,
            'lintang' => -7.185,
            'bujur' => 109.588,
            'poligon' => null,
        ];
    }

    public function alarm(): static
    {
        return $this->state(fn () => [
            'jenis' => Titik::JENIS_ALARM,
            'poligon' => [[-7.1858, 109.5887], [-7.1851, 109.5878], [-7.1854, 109.5875], [-7.1861, 109.5884]],
        ]);
    }
}
