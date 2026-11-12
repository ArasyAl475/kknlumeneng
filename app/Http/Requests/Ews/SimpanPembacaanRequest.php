<?php

namespace App\Http\Requests\Ews;

use App\Models\Ews\Wilayah;
use App\Services\Ews\KirimanAlat;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/** Isi kiriman alat. Kontrak: docs/ews/API_ALAT.md, jangan diubah tanpa pembuat alat. */
class SimpanPembacaanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(KirimanAlat::normalisasi($this->all()));
    }

    public function rules(): array
    {
        return [
            'wilayah_id' => ['required_without:lokasi', 'nullable', 'integer', Rule::exists('ews_wilayah', 'id_alat')->whereNull('deleted_at')],
            'lokasi' => ['required_without:wilayah_id', 'nullable', 'string', 'max:100', Rule::exists('ews_wilayah', 'nama')->whereNull('deleted_at')],
            ...KirimanAlat::aturanSensor(),
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => KirimanAlat::periksaAdaIsi($validator, $this->all())];
    }

    // Bentuk 422 mengikuti kontrak alat: {"pesan", "errors"}.
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'pesan' => 'Data tidak valid',
            'errors' => $validator->errors(),
        ], 422));
    }

    // wilayah_id dari alat = ID alat (kolom id_alat), bukan id baris basis data; admin bisa menggantinya.
    public function wilayah(): Wilayah
    {
        return $this->filled('wilayah_id')
            ? Wilayah::query()->where('id_alat', $this->integer('wilayah_id'))->firstOrFail()
            : Wilayah::query()->where('nama', $this->input('lokasi'))->firstOrFail();
    }

    /**
     * @return array<string, float|null>
     */
    public function bacaan(): array
    {
        return KirimanAlat::bacaan($this->validated());
    }
}
