<?php

namespace App\Http\Requests\Edukasi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KategoriRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100', Rule::unique('edukasi_kategori', 'nama')->ignore($this->route('kategori'))],
            'tips_sirkular' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['nama.unique' => 'Nama kategori sudah dipakai.'];
    }

    public function attributes(): array
    {
        return ['nama' => 'nama kategori', 'tips_sirkular' => 'tips sirkularitas'];
    }
}
