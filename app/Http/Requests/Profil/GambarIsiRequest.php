<?php

namespace App\Http\Requests\Profil;

use App\Services\Profil\PenyimpanGambar;
use Illuminate\Foundation\Http\FormRequest;

// Foto yang disisipkan di tengah tulisan lewat tombol gambar di editor.
class GambarIsiRequest extends FormRequest
{
    public function rules(): array
    {
        return ['gambar' => ['required', ...array_slice(PenyimpanGambar::aturan(), 1)]];
    }

    public function messages(): array
    {
        return PenyimpanGambar::pesan('gambar') + ['gambar.required' => 'Pilih foto yang ingin disisipkan.'];
    }
}
