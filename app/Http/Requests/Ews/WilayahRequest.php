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
            // ID yang dipasang di alat. Kosong = otomatis (baru) atau tidak berubah (ubah). Wilayah terhapus melepas angkanya.
            'id_alat' => ['nullable', 'integer', 'between:1,999999', Rule::unique('ews_wilayah', 'id_alat')->ignore($this->route('wilayah'))],
            // Termasuk yang terhapus lunak: nama lama masih menunjuk riwayat, dan bisa dipulihkan.
            'nama' => ['required', 'string', 'max:100', Rule::unique('ews_wilayah', 'nama')->ignore($this->route('wilayah'))],
            'poligon' => ['required', 'string', function (string $kolom, mixed $nilai, Closure $gagal): void {
                $titik = GeometriPoligon::dariTeks((string) $nilai);
                $masalah = $titik === null ? 'Format batas daerah tidak valid.' : GeometriPoligon::masalah($titik);

                if ($masalah !== null) {
                    $gagal($masalah);
                }
            }],
            // Lokasi alat EWS: boleh kosong, tetapi lintang dan bujur harus diisi berpasangan.
            'lintang' => ['nullable', 'required_with:bujur', 'numeric', 'between:-90,90'],
            'bujur' => ['nullable', 'required_with:lintang', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_alat.unique' => 'ID alat itu sudah dipakai wilayah lain. Pilih angka yang berbeda.',
            'id_alat.integer' => 'ID alat harus berupa angka bulat, misalnya 1.',
            'id_alat.between' => 'ID alat harus antara 1 dan 999999.',
            'nama.unique' => 'Nama daerah sudah dipakai, termasuk oleh daerah yang pernah dihapus.',
            'poligon.required' => 'Gambar dulu bentuk daerahnya di peta.',
            'lintang.required_with' => 'Lintang harus diisi bersama bujur.',
            'bujur.required_with' => 'Bujur harus diisi bersama lintang.',
        ];
    }

    /**
     * @return array{nama: string, poligon: array<int, array{0: float, 1: float}>, lintang: ?float, bujur: ?float, id_alat?: int}
     */
    public function dataWilayah(): array
    {
        $data = [
            'nama' => $this->validated('nama'),
            'poligon' => GeometriPoligon::dariTeks($this->validated('poligon')),
            'lintang' => $this->filled('lintang') ? round((float) $this->validated('lintang'), 6) : null,
            'bujur' => $this->filled('bujur') ? round((float) $this->validated('bujur'), 6) : null,
        ];

        // Kosong berarti: otomatis untuk wilayah baru, tidak berubah untuk wilayah lama.
        if ($this->filled('id_alat')) {
            $data['id_alat'] = $this->integer('id_alat');
        }

        return $data;
    }
}
