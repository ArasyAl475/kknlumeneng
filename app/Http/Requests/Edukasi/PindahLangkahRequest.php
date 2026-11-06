<?php

namespace App\Http\Requests\Edukasi;

use Illuminate\Foundation\Http\FormRequest;

class PindahLangkahRequest extends FormRequest
{
    public function rules(): array
    {
        return ['arah' => ['required', 'in:naik,turun']];
    }
}
