<?php

namespace App\Http\Requests\Toko;

use Illuminate\Foundation\Http\FormRequest;

class ImporHasilRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'berkas' => ['required', 'file', 'mimes:xlsx', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'berkas.required' => 'Pilih berkas Excel yang mau diunggah.',
            'berkas.mimes' => 'Berkas harus berformat Excel (.xlsx). Gunakan templat yang bisa diunduh di halaman ini.',
            'berkas.max' => 'Ukuran berkas paling besar 2 MB. Pecah menjadi beberapa berkas.',
            'berkas.uploaded' => 'Berkas gagal diunggah. Coba lagi; jika tetap gagal, ukurannya mungkin terlalu besar (paling besar 2 MB).',
        ];
    }
}
