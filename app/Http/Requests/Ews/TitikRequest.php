<?php

namespace App\Http\Requests\Ews;

use App\Models\Ews\Titik;
use App\Services\Ews\GeometriPoligon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TitikRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100', Rule::unique('ews_titik', 'nama')->ignore($this->route('titik'))],
            'jenis' => ['required', Rule::in(array_keys(Titik::JENIS))],
            'lintang' => ['required', 'numeric', 'between:-90,90'],
            'bujur' => ['required', 'numeric', 'between:-180,180'],
            // Batas area hanya pelengkap (mis. area EWS alarm); titik rawan cukup satu koordinat.
            'poligon' => ['nullable', 'string', function (string $kolom, mixed $nilai, Closure $gagal): void {
                $titik = GeometriPoligon::dariTeks((string) $nilai);
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
            'nama.unique' => 'Nama titik sudah dipakai.',
            'jenis.required' => 'Pilih jenis titiknya.',
            'jenis.in' => 'Jenis titik tidak dikenal.',
            'lintang.required' => 'Isi lintang titiknya, atau ketuk titik di peta.',
            'bujur.required' => 'Isi bujur titiknya, atau ketuk titik di peta.',
        ];
    }

    /**
     * @return array{nama: string, jenis: string, lintang: float, bujur: float, poligon: ?array<int, array{0: float, 1: float}>}
     */
    public function dataTitik(): array
    {
        return [
            'nama' => $this->validated('nama'),
            'jenis' => $this->validated('jenis'),
            'lintang' => round((float) $this->validated('lintang'), 6),
            'bujur' => round((float) $this->validated('bujur'), 6),
            'poligon' => $this->filled('poligon') ? GeometriPoligon::dariTeks($this->validated('poligon')) : null,
        ];
    }
}
