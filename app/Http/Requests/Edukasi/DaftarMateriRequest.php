<?php

namespace App\Http\Requests\Edukasi;

use Illuminate\Foundation\Http\FormRequest;

class DaftarMateriRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cari' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'max:170'],
        ];
    }

    public function attributes(): array
    {
        return ['cari' => 'kata pencarian'];
    }
}
