<?php

namespace Database\Factories\Edukasi;

use App\Models\Edukasi\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kategori>
 */
class KategoriFactory extends Factory
{
    protected $model = Kategori::class;

    public function definition(): array
    {
        $nomor = fake()->unique()->numberBetween(1, 99999);

        return ['nama' => "Kategori {$nomor}", 'slug' => "kategori-{$nomor}"];
    }
}
