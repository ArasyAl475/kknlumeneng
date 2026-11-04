<?php

namespace App\Http\Requests\Ews;

use App\Services\Ews\DataGrafik;
use App\Services\Ews\Sensor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GrafikRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'wilayah' => ['required', 'integer', Rule::exists('ews_wilayah', 'id')->whereNull('deleted_at')],
            'sensor' => ['required', Rule::in(array_keys(Sensor::DAFTAR))],
            'rentang' => ['required', Rule::in(array_keys(DataGrafik::RENTANG))],
        ];
    }
}
