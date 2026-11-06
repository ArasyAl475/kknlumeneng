<?php

namespace App\Http\Requests\Toko;

use Illuminate\Foundation\Http\FormRequest;

class PengaturanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nama_penjual' => ['required', 'string', 'max:100'],
            'keterangan_penjual' => ['nullable', 'string', 'max:300'],
            'lokasi_kirim' => ['required', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_penjual' => 'nama penjual',
            'keterangan_penjual' => 'keterangan penjual',
            'lokasi_kirim' => 'lokasi pengiriman',
        ];
    }
}
