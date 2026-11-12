<?php

namespace App\Http\Requests\Profil;

use App\Models\Profil\Potensi;
use App\Services\Profil\PenyimpanGambar;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class PotensiRequest extends FormRequest
{
    // Sesuai batas jumlah berkas PHP bawaan (max_file_uploads = 20), disisakan untuk isian lain.
    public const MAKS_FOTO_SEKALIGUS = 10;

    // Lebih dari ini kolase sulit dinikmati; foto di atas 6 hanya muncul di penampil foto.
    public const MAKS_FOTO_TOTAL = 12;

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:120'],
            'kategori' => ['required', Rule::in(array_keys(Potensi::KATEGORI))],
            'hasil' => ['nullable', 'string', 'max:1000'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'luas' => ['nullable', 'string', 'max:40'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
            'fotos' => ['nullable', 'array', 'max:'.self::MAKS_FOTO_SEKALIGUS],
            'fotos.*' => PenyimpanGambar::aturan(),
            'hapus_foto' => ['nullable', 'array'],
            'hapus_foto.*' => ['integer'],
            'utama' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return PenyimpanGambar::pesan('fotos.*') + [
            'kategori.required' => 'Pilih jenis potensinya.',
            'kategori.in' => 'Jenis potensi tidak dikenal.',
            'fotos.max' => 'Paling banyak '.self::MAKS_FOTO_SEKALIGUS.' foto sekali simpan. Sisanya tambahkan setelah disimpan.',
        ];
    }

    public function attributes(): array
    {
        return ['fotos.*' => 'foto'];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $potensi = $this->route('potensi');
            $ada = $potensi instanceof Potensi ? $potensi->fotos()->count() : 0;
            $dihapus = $potensi instanceof Potensi ? $potensi->fotos()->whereIn('id', $this->hapusFoto())->count() : 0;

            if ($ada - $dihapus + count($this->fotoBaru()) > self::MAKS_FOTO_TOTAL) {
                $validator->errors()->add('fotos', 'Satu potensi paling banyak '.self::MAKS_FOTO_TOTAL.' foto. Hapus beberapa foto lama atau kurangi yang diunggah.');
            }
        }];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataPotensi(): array
    {
        $data = Arr::except($this->validated(), ['fotos', 'hapus_foto', 'utama']);
        $data['urutan'] = (int) ($data['urutan'] ?? 0);

        return $data;
    }

    /**
     * @return array<int, UploadedFile>
     */
    public function fotoBaru(): array
    {
        return array_values(array_filter((array) $this->file('fotos', [])));
    }

    /**
     * @return array<int, int>
     */
    public function hapusFoto(): array
    {
        return array_map('intval', (array) $this->input('hapus_foto', []));
    }

    public function fotoUtama(): ?int
    {
        return $this->filled('utama') ? $this->integer('utama') : null;
    }
}
