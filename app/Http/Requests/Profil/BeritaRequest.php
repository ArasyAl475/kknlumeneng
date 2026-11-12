<?php

namespace App\Http\Requests\Profil;

use App\Models\Profil\Berita;
use App\Services\Profil\IsiKaya;
use App\Services\Profil\PenyimpanGambar;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class BeritaRequest extends FormRequest
{
    // Sesuai batas jumlah berkas PHP bawaan (max_file_uploads = 20).
    public const MAKS_FOTO_SEKALIGUS = 15;

    public function rules(): array
    {
        return [
            'jenis' => ['required', Rule::in(array_keys(Berita::JENIS))],
            'judul' => ['required', 'string', 'max:150'],
            'ringkasan' => ['required', 'string', 'max:300'],
            // Batas dihitung dari HTML editor, jadi jauh lebih longgar daripada jumlah huruf yang terlihat.
            'isi' => ['required', 'string', 'max:200000', function (string $kolom, mixed $nilai, Closure $gagal): void {
                if (IsiKaya::bersihkan((string) $nilai) === '') {
                    $gagal('Isi tulisan masih kosong.');
                }
            }],
            'penulis' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['terbit', 'draf'])],
            'gambar' => PenyimpanGambar::aturan(),
            'hapus_gambar' => ['nullable', 'boolean'],
            'foto_kegiatan' => ['nullable', 'array', 'max:'.self::MAKS_FOTO_SEKALIGUS],
            'foto_kegiatan.*' => PenyimpanGambar::aturan(),
        ];
    }

    public function messages(): array
    {
        return PenyimpanGambar::pesan('gambar') + PenyimpanGambar::pesan('foto_kegiatan.*') + [
            'jenis.in' => 'Pilih jenis tulisan dari daftar yang tersedia.',
            'status.in' => 'Pilih Terbitkan sekarang atau Simpan sebagai draf.',
            'foto_kegiatan.max' => 'Paling banyak '.self::MAKS_FOTO_SEKALIGUS.' foto kegiatan sekali simpan. Sisanya tambahkan setelah disimpan.',
        ];
    }

    public function attributes(): array
    {
        return ['jenis' => 'jenis tulisan', 'isi' => 'isi tulisan', 'foto_kegiatan.*' => 'foto kegiatan'];
    }

    public function terbit(): bool
    {
        return $this->validated('status') === 'terbit';
    }

    /**
     * @return array<string, mixed>
     */
    public function dataBerita(): array
    {
        $data = Arr::except($this->validated(), ['gambar', 'hapus_gambar', 'status', 'foto_kegiatan']);
        $data['isi'] = IsiKaya::bersihkan($data['isi']);

        return $data;
    }

    /**
     * @return array<int, UploadedFile>
     */
    public function fotoKegiatan(): array
    {
        return array_values(array_filter((array) $this->file('foto_kegiatan', [])));
    }
}
