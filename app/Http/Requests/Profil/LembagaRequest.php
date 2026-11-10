<?php

namespace App\Http\Requests\Profil;

use Illuminate\Foundation\Http\FormRequest;

class LembagaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'singkatan' => ['nullable', 'string', 'max:20'],
            'ketua' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:300'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataLembaga(): array
    {
        return ['urutan' => (int) $this->validated('urutan', 0)] + $this->validated();
    }
}
