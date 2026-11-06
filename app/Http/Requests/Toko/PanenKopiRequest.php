<?php

namespace App\Http\Requests\Toko;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class PanenKopiRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Tulisan "robusta" dan "Robusta " harus masuk satu baris rekap, bukan dua.
        foreach (['jenis_kopi', 'tempat_panen'] as $kolom) {
            if (is_string($this->input($kolom))) {
                $this->merge([$kolom => Str::title(Str::squish($this->input($kolom)))]);
            }
        }

        // Nama orang/kelompok tidak diubah huruf besar-kecilnya ("KWT" tidak boleh jadi "Kwt"), hanya dirapikan spasinya.
        if (is_string($this->input('nama_petani'))) {
            $this->merge(['nama_petani' => Str::squish($this->input('nama_petani')) ?: null]);
        }
    }

    public function rules(): array
    {
        return [
            'tanggal_panen' => ['required', 'date', 'before_or_equal:today'],
            'jenis_kopi' => ['required', 'string', 'max:100'],
            'tempat_panen' => ['required', 'string', 'max:150'],
            'nama_petani' => ['nullable', 'string', 'max:150'],
            'jumlah_kg' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'limbah_kg' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.decimal' => ':Attribute paling banyak memakai 2 angka di belakang koma.',
            '*.before_or_equal' => ':Attribute tidak boleh melewati hari ini.',
        ];
    }

    public function attributes(): array
    {
        return [
            'tanggal_panen' => 'tanggal panen',
            'jenis_kopi' => 'jenis kopi',
            'tempat_panen' => 'tempat panen',
            'nama_petani' => 'nama petani/kelompok',
            'jumlah_kg' => 'jumlah panen',
            'limbah_kg' => 'jumlah limbah',
        ];
    }
}
