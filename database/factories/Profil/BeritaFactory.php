<?php

namespace Database\Factories\Profil;

use App\Models\Profil\Berita;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Berita>
 */
class BeritaFactory extends Factory
{
    protected $model = Berita::class;

    public function definition(): array
    {
        $judul = 'Kabar desa '.Str::lower(Str::random(8));

        return [
            'jenis' => 'berita',
            'judul' => $judul,
            'slug' => Str::slug($judul),
            'ringkasan' => 'Ringkasan kabar desa.',
            'isi' => "Paragraf pertama.\n\nParagraf kedua.",
            'penulis' => 'Admin Desa',
            'diterbitkan_pada' => now()->subDay(),
        ];
    }

    public function draf(): static
    {
        return $this->state(fn () => ['diterbitkan_pada' => null]);
    }
}
