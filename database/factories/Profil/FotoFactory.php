<?php

namespace Database\Factories\Profil;

use App\Models\Profil\Foto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Foto>
 */
class FotoFactory extends Factory
{
    protected $model = Foto::class;

    public function definition(): array
    {
        return [
            'berita_id' => null,
            'gambar' => 'profil/'.Str::random(20).'.webp',
            'judul' => 'Foto '.Str::lower(Str::random(6)),
            'keterangan' => null,
            'urutan' => 0,
        ];
    }
}
