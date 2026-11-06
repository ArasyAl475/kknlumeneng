<?php

namespace App\Http\Requests\Edukasi;

use App\Services\Edukasi\PengolahGambar;
use App\Services\Edukasi\TautanYoutube;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class MateriRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'kategori_id' => ['required', 'integer', 'exists:edukasi_kategori,id'],
            'judul' => ['required', 'string', 'max:150'],
            'ringkasan' => ['required', 'string', 'max:300'],
            'penjelasan' => ['nullable', 'string', 'max:10000'],
            'gambar' => PengolahGambar::aturan(),
            'hapus_gambar' => ['nullable', 'boolean'],
            'video_youtube' => ['nullable', 'string', 'max:200', function (string $kolom, mixed $nilai, Closure $gagal): void {
                if (TautanYoutube::kode($nilai) === null) {
                    $gagal('Tautan YouTube tidak dikenali. Salin tautan lengkap dari tombol Bagikan di YouTube.');
                }
            }],
        ];
    }

    public function messages(): array
    {
        return PengolahGambar::pesan() + ['kategori_id.exists' => 'Kategori yang dipilih tidak ditemukan.'];
    }

    public function attributes(): array
    {
        return [
            'kategori_id' => 'kategori',
            'video_youtube' => 'tautan YouTube',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataMateri(): array
    {
        $data = Arr::except($this->validated(), ['gambar', 'hapus_gambar']);

        // Yang disimpan hanya kode video, bukan tautan yang ditempel admin.
        if (array_key_exists('video_youtube', $data)) {
            $data['video_youtube'] = TautanYoutube::kode($data['video_youtube']);
        }

        return $data;
    }
}
