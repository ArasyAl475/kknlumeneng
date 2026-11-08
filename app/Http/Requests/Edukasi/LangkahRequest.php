<?php

namespace App\Http\Requests\Edukasi;

use App\Services\Edukasi\PengolahGambar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class LangkahRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:150'],
            'isi' => ['required', 'string', 'max:5000'],
            'gambar' => PengolahGambar::aturan(),
            'hapus_gambar' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return PengolahGambar::pesan();
    }

    public function attributes(): array
    {
        return ['judul' => 'judul langkah', 'isi' => 'isi langkah'];
    }

    /**
     * @return array{judul: string, isi: string}
     */
    public function dataLangkah(): array
    {
        return Arr::only($this->validated(), ['judul', 'isi']);
    }
}
