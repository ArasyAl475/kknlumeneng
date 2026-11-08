<?php

namespace App\Http\Requests\Toko;

use Illuminate\Foundation\Http\FormRequest;

class PenjualanJamurRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tanggal_penjualan' => ['required', 'date', 'before_or_equal:today'],
            'jumlah_kg' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'dengan_cangkang_kopi' => ['required', 'boolean'],
            'total_rupiah' => ['required', 'integer', 'min:0', 'max:999999999999'],
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
            'tanggal_penjualan' => 'tanggal penjualan',
            'jumlah_kg' => 'jumlah terjual',
            'dengan_cangkang_kopi' => 'media tanam',
            'total_rupiah' => 'uang diterima',
        ];
    }
}
