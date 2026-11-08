<?php

namespace App\Http\Requests\Toko;

use Illuminate\Foundation\Http\FormRequest;

class RekapRequest extends FormRequest
{
    // Tautan berisi filter salah kembali ke halaman bersih, bukan ke halaman sebelumnya yang tak pasti.
    protected $redirectRoute = 'toko.hasil';

    public function rules(): array
    {
        return [
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'jenis' => ['nullable', 'string', 'max:100'],
            'tempat' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function attributes(): array
    {
        return ['dari' => 'tanggal mulai', 'sampai' => 'tanggal akhir'];
    }
}
