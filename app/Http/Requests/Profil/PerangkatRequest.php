<?php

namespace App\Http\Requests\Profil;

use App\Models\Profil\Perangkat;
use App\Services\Profil\PenyimpanGambar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class PerangkatRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'jabatan' => ['required', 'string', 'max:100'],
            'kelompok' => ['required', Rule::in(array_keys(Perangkat::KELOMPOK))],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
            'foto' => PenyimpanGambar::aturan(),
            'hapus_foto' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return PenyimpanGambar::pesan('foto') + ['kelompok.in' => 'Pilih letak di bagan dari daftar yang tersedia.'];
    }

    public function attributes(): array
    {
        return ['kelompok' => 'letak di bagan'];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataPerangkat(): array
    {
        $data = Arr::except($this->validated(), ['foto', 'hapus_foto']);
        $data['urutan'] = (int) ($data['urutan'] ?? 0);

        return $data;
    }
}
