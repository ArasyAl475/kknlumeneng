<?php

namespace Database\Factories\Edukasi;

use App\Models\Edukasi\Langkah;
use App\Models\Edukasi\Materi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Langkah>
 */
class LangkahFactory extends Factory
{
    protected $model = Langkah::class;

    public function definition(): array
    {
        $nomor = fake()->unique()->numberBetween(1, 99999);

        return [
            'materi_id' => Materi::factory(),
            'urutan' => $nomor,
            'judul' => "Langkah {$nomor}",
            'isi' => "Isi langkah {$nomor}.",
        ];
    }
}
