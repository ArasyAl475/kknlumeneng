<?php

namespace App\Http\Requests\Toko;

use Illuminate\Foundation\Http\FormRequest;

class PanenJamurRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tanggal_panen' => ['required', 'date', 'before_or_equal:today'],
            'jumlah_kg' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'dengan_cangkang_kopi' => ['required', 'boolean'],
            'jumlah_baglog' => ['required', 'integer', 'min:1', 'max:1000000'],
            'biaya_baglog_rupiah' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.decimal' => ':Attribute paling banyak memakai 2 angka di belakang koma.',
            '*.before_or_equal' => ':Attribute tidak boleh melewati hari ini.',
        ];
    }

    public function attributes(): array
    {
        return [
            'tanggal_panen' => 'tanggal panen',
            'jumlah_kg' => 'jumlah panen',
            'dengan_cangkang_kopi' => 'media tanam',
            'jumlah_baglog' => 'jumlah baglog',
            'biaya_baglog_rupiah' => 'biaya membuat baglog',
        ];
    }
}
