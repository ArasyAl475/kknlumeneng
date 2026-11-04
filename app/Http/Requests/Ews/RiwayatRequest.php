<?php

namespace App\Http\Requests\Ews;

use Illuminate\Foundation\Http\FormRequest;

class RiwayatRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'wilayah' => ['nullable', 'integer', 'exists:ews_wilayah,id'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ];
    }

    public function attributes(): array
    {
        return ['dari' => 'tanggal mulai', 'sampai' => 'tanggal akhir'];
    }
}
