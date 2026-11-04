<?php

namespace App\Http\Requests\Ews;

use App\Models\Ews\Wilayah;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/** Isi kiriman alat. Kontrak: docs/ews/API_ALAT.md, jangan diubah tanpa pembuat alat. */
class SimpanPembacaanRequest extends FormRequest
{
    private const SENSOR = ['kelembapan_tanah', 'curah_hujan', 'kemiringan', 'kelembapan_udara'];

    protected function prepareForValidation(): void
    {
        // Firmware lama masih mengirim nama "kelembapan".
        if ($this->input('kelembapan_tanah') === null && $this->input('kelembapan') !== null) {
            $this->merge(['kelembapan_tanah' => $this->input('kelembapan')]);
        }
    }

    public function rules(): array
    {
        return [
            'wilayah_id' => ['required_without:lokasi', 'nullable', 'integer', Rule::exists('ews_wilayah', 'id')->whereNull('deleted_at')],
            'lokasi' => ['required_without:wilayah_id', 'nullable', 'string', 'max:100', Rule::exists('ews_wilayah', 'nama')->whereNull('deleted_at')],
            'kelembapan_tanah' => ['nullable', 'numeric', 'between:0,100'],
            'curah_hujan' => ['nullable', 'numeric', 'between:0,1000'],
            'kemiringan' => ['nullable', 'numeric', 'between:-90,90'],
            'kelembapan_udara' => ['nullable', 'numeric', 'between:0,100'],
            'kabel_putus' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $adaSensor = collect(self::SENSOR)->contains(fn (string $sensor) => $this->input($sensor) !== null);

            if (! $adaSensor && ! $this->boolean('kabel_putus')) {
                $validator->errors()->add('kelembapan_tanah', 'Kirim minimal satu nilai sensor atau kabel_putus: true.');
            }
        }];
    }

    // Bentuk 422 mengikuti kontrak alat: {"pesan", "errors"}.
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'pesan' => 'Data tidak valid',
            'errors' => $validator->errors(),
        ], 422));
    }

    public function wilayah(): Wilayah
    {
        return $this->filled('wilayah_id')
            ? Wilayah::query()->findOrFail($this->integer('wilayah_id'))
            : Wilayah::query()->where('nama', $this->input('lokasi'))->firstOrFail();
    }

    /**
     * @return array<string, float|null>
     */
    public function bacaan(): array
    {
        $bacaan = [];

        foreach (self::SENSOR as $sensor) {
            $nilai = $this->validated($sensor);
            $bacaan[$sensor] = $nilai === null ? null : round((float) $nilai, 2);
        }

        return $bacaan;
    }
}
