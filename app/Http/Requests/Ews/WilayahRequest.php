<?php

namespace App\Http\Requests\Ews;

use App\Services\Ews\GeometriPoligon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WilayahRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Termasuk yang terhapus lunak: nama lama masih menunjuk riwayat, dan bisa dipulihkan.
            'nama' => ['required', 'string', 'max:100', Rule::unique('ews_wilayah', 'nama')->ignore($this->route('wilayah'))],
            'poligon' => ['required', 'string', function (string $kolom, mixed $nilai, Closure $gagal): void {
                $titik = GeometriPoligon::normalkan(json_decode((string) $nilai, true));
                $masalah = $titik === null ? 'Format batas daerah tidak valid.' : GeometriPoligon::masalah($titik);

                if ($masalah !== null) {
                    $gagal($masalah);
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.unique' => 'Nama daerah sudah dipakai, termasuk oleh daerah yang pernah dihapus.',
            'poligon.required' => 'Gambar dulu bentuk daerahnya di peta.',
        ];
    }

    /**
     * @return array{nama: string, poligon: array<int, array{0: float, 1: float}>}
     */
    public function dataWilayah(): array
    {
        return [
            'nama' => $this->validated('nama'),
            'poligon' => GeometriPoligon::normalkan(json_decode($this->validated('poligon'), true)),
        ];
    }
}
