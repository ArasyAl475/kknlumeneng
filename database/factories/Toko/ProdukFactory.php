<?php

namespace Database\Factories\Toko;

use App\Models\Toko\Produk;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Produk>
 */
class ProdukFactory extends Factory
{
    protected $model = Produk::class;

    public function definition(): array
    {
        $nama = 'Produk '.fake()->unique()->numberBetween(1, 99999);

        return [
            'nama' => $nama,
            'slug' => Str::slug($nama),
            'deskripsi' => 'Deskripsi contoh.',
            'harga' => 25000,
            'tersedia' => true,
            'tampil' => true,
            'urutan' => 0,
        ];
    }

    public function tersembunyi(): static
    {
        return $this->state(fn () => ['tampil' => false]);
    }

    /**
     * @param  array<string, string>  $tautan  kunci marketplace => url
     */
    public function denganTautan(array $tautan): static
    {
        return $this->afterCreating(function (Produk $produk) use ($tautan) {
            foreach ($tautan as $marketplace => $url) {
                $produk->tautan()->create(['marketplace' => $marketplace, 'url' => $url]);
            }
        });
    }
}
