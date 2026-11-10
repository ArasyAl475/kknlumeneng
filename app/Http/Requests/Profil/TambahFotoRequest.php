<?php

namespace App\Http\Requests\Profil;

use App\Services\Profil\PenyimpanGambar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

/**
 * Unggah foto desa mandiri lewat menu Galeri. Foto kegiatan TIDAK lewat sini: foto itu dibuat
 * otomatis dari formulir berita, agar admin tidak perlu memahami cara "menghubungkan" data.
 */
class TambahFotoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'foto' => ['required', 'array', 'min:1', 'max:'.BeritaRequest::MAKS_FOTO_SEKALIGUS],
            'foto.*' => ['required', ...array_slice(PenyimpanGambar::aturan(), 1)],
            'judul' => ['nullable', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'tanggal' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return PenyimpanGambar::pesan('foto.*') + [
            'foto.required' => 'Pilih minimal satu foto.',
            'foto.max' => 'Paling banyak '.BeritaRequest::MAKS_FOTO_SEKALIGUS.' foto sekali unggah.',
            'tanggal.before_or_equal' => 'Tanggal foto tidak boleh di masa depan.',
        ];
    }

    public function attributes(): array
    {
        return ['foto.*' => 'foto'];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataFoto(): array
    {
        return Arr::except($this->validated(), ['foto']) + ['berita_id' => null];
    }

    /**
     * @return array<int, UploadedFile>
     */
    public function berkas(): array
    {
        return array_values(array_filter((array) $this->file('foto', [])));
    }
}
