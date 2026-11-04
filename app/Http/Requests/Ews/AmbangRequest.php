<?php

namespace App\Http\Requests\Ews;

use Illuminate\Foundation\Http\FormRequest;

class AmbangRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'kelembapan_tanah_waspada' => ['required', 'numeric', 'between:1,99'],
            'kelembapan_tanah_validasi' => ['required', 'numeric', 'gt:kelembapan_tanah_waspada', 'max:100'],
            'curah_hujan_waspada' => ['required', 'numeric', 'between:0.1,999'],
            'curah_hujan_validasi' => ['required', 'numeric', 'gt:curah_hujan_waspada', 'max:1000'],
            'kemiringan_waspada' => ['required', 'numeric', 'between:0.1,89'],
            'kemiringan_validasi' => ['required', 'numeric', 'gt:kemiringan_waspada', 'max:90'],
        ];
    }

    public function messages(): array
    {
        return [
            'kelembapan_tanah_validasi.gt' => 'Batas validasi kelembapan tanah harus lebih besar dari batas waspada.',
            'curah_hujan_validasi.gt' => 'Batas validasi curah hujan harus lebih besar dari batas waspada.',
            'kemiringan_validasi.gt' => 'Batas validasi kemiringan harus lebih besar dari batas waspada.',
        ];
    }

    public function attributes(): array
    {
        return [
            'kelembapan_tanah_waspada' => 'batas waspada kelembapan tanah',
            'kelembapan_tanah_validasi' => 'batas validasi kelembapan tanah',
            'curah_hujan_waspada' => 'batas waspada curah hujan',
            'curah_hujan_validasi' => 'batas validasi curah hujan',
            'kemiringan_waspada' => 'batas waspada kemiringan',
            'kemiringan_validasi' => 'batas validasi kemiringan',
        ];
    }

    /**
     * @return array<string, array{waspada: float, validasi: float}>
     */
    public function ambang(): array
    {
        $d = $this->validated();
        $hasil = [];

        foreach (['kelembapan_tanah', 'curah_hujan', 'kemiringan'] as $sensor) {
            $hasil[$sensor] = ['waspada' => (float) $d["{$sensor}_waspada"], 'validasi' => (float) $d["{$sensor}_validasi"]];
        }

        return $hasil;
    }
}
