<?php

namespace App\Http\Requests\Profil;

use App\Services\Profil\PenyimpanGambar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

/**
 * Ubah satu foto galeri. Sambungan ke berita sengaja tidak bisa diubah di sini: foto kegiatan
 * diatur dari halaman beritanya sendiri.
 */
class FotoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'foto' => PenyimpanGambar::aturan(),
            'judul' => ['nullable', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'tanggal' => ['nullable', 'date', 'before_or_equal:today'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return PenyimpanGambar::pesan('foto') + ['tanggal.before_or_equal' => 'Tanggal foto tidak boleh di masa depan.'];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataFoto(): array
    {
        $data = Arr::except($this->validated(), ['foto']);
        $data['urutan'] = (int) ($data['urutan'] ?? 0);

        return $data;
    }
}
