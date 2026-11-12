<?php

namespace App\Http\Requests\Desain;

use App\Models\Desain\SlotGambar;
use App\Services\Desain\PenyimpanGambarSitus;
use Illuminate\Foundation\Http\FormRequest;

class SlotGambarRequest extends FormRequest
{
    /**
     * Banyak formulir slot ada di satu halaman. Tiap slot memakai kantong galat sendiri,
     * agar pesan "format salah" hanya muncul di kartu slot yang baru saja dikirim.
     */
    protected function prepareForValidation(): void
    {
        $this->errorBag = 'slot'.$this->slot()->id;
    }

    public function slot(): SlotGambar
    {
        return $this->route('slot');
    }

    public function rules(): array
    {
        return [
            'gambar' => PenyimpanGambarSitus::aturan(),
            'alt' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return PenyimpanGambarSitus::pesan('gambar') + [
            'alt.max' => 'Teks alternatif paling panjang 200 huruf.',
        ];
    }

    // Kembali tepat ke kartu slot yang dikirim, bukan ke atas halaman.
    protected function getRedirectUrl(): string
    {
        return route('admin.desain.index').'#slot-'.$this->slot()->id;
    }
}
