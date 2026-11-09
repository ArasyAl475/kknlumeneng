<?php

namespace App\Http\Requests\Profil;

use Illuminate\Foundation\Http\FormRequest;

class DaftarBeritaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cari' => ['nullable', 'string', 'max:100'],
            // Jenis yang tidak dikenal cukup menghasilkan daftar kosong, bukan halaman galat.
            'jenis' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return ['cari' => 'kata pencarian'];
    }
}
