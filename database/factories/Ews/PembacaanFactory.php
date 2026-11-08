<?php

namespace Database\Factories\Ews;

use App\Models\Ews\Pembacaan;
use App\Models\Ews\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pembacaan>
 */
class PembacaanFactory extends Factory
{
    protected $model = Pembacaan::class;

    public function definition(): array
    {
        return [
            'wilayah_id' => Wilayah::factory(),
            'kelembapan_tanah' => 30,
            'intensitas_hujan' => 5,
            'kemiringan' => 0.5,
            'kelembapan_udara' => 70,
            'kabel_putus' => false,
            'status' => 'Aman',
            'warna' => 'hijau',
        ];
    }

    public function menunggu(): static
    {
        return $this->state(fn () => [
            'kelembapan_tanah' => 90,
            'status' => 'Waspada',
            'warna' => 'kuning',
            'pemicu' => 'Kelembapan tanah 90%',
            'validasi' => Pembacaan::VALIDASI_MENUNGGU,
        ]);
    }
}
