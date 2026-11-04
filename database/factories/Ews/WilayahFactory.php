<?php

namespace Database\Factories\Ews;

use App\Models\Ews\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wilayah>
 */
class WilayahFactory extends Factory
{
    protected $model = Wilayah::class;

    public function definition(): array
    {
        return [
            'nama' => 'Wilayah '.fake()->unique()->numberBetween(1, 99999),
            'poligon' => [[-7.17, 109.55], [-7.17, 109.56], [-7.18, 109.56], [-7.18, 109.55]],
        ];
    }

    public function awas(string $sumber = 'Dinyatakan admin setelah validasi'): static
    {
        return $this->state(fn () => ['status_awas' => true, 'awas_sumber' => $sumber, 'awas_sejak' => now()]);
    }
}
