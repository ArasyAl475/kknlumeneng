<?php

namespace Database\Factories\Edukasi;

use App\Models\Edukasi\Kategori;
use App\Models\Edukasi\Materi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Materi>
 */
class MateriFactory extends Factory
{
    protected $model = Materi::class;

    public function definition(): array
    {
        $nomor = fake()->unique()->numberBetween(1, 99999);

        return [
            'kategori_id' => Kategori::factory(),
            'judul' => "Materi {$nomor}",
            'slug' => "materi-{$nomor}",
            'ringkasan' => "Ringkasan materi {$nomor}.",
            'penjelasan' => "Penjelasan materi {$nomor}.",
        ];
    }

    public function terbit(): static
    {
        return $this->state(fn () => ['diterbitkan_pada' => now()]);
    }
}
