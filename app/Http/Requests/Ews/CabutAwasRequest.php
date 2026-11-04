<?php

namespace App\Http\Requests\Ews;

use Illuminate\Foundation\Http\FormRequest;

class CabutAwasRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'min:5', 'max:500'],
            'konfirmasi' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return ['konfirmasi.accepted' => 'Centang pernyataan bahwa situasi sudah diperiksa.'];
    }
}
