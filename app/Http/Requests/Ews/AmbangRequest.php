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
            'intensitas_hujan_waspada' => ['required', 'numeric', 'between:1,99'],
            'intensitas_hujan_validasi' => ['required', 'numeric', 'gt:intensitas_hujan_waspada', 'max:100'],
            'kemiringan_waspada' => ['required', 'numeric', 'between:0.1,89'],
            'kemiringan_validasi' => ['required', 'numeric', 'gt:kemiringan_waspada', 'max:90'],
        ];
    }

    public function messages(): array
    {
        return [
            'kelembapan_tanah_validasi.gt' => 'Batas validasi kelembapan tanah harus lebih besar dari batas waspada.',
            'intensitas_hujan_validasi.gt' => 'Batas validasi intensitas hujan harus lebih besar dari batas waspada.',
            'kemiringan_validasi.gt' => 'Batas validasi kemiringan harus lebih besar dari batas waspada.',
        ];
    }

    public function attributes(): array
    {
        return [
            'kelembapan_tanah_waspada' => 'batas waspada kelembapan tanah',
            'kelembapan_tanah_validasi' => 'batas validasi kelembapan tanah',
            'intensitas_hujan_waspada' => 'batas waspada intensitas hujan',
            'intensitas_hujan_validasi' => 'batas validasi intensitas hujan',
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

        foreach (['kelembapan_tanah', 'intensitas_hujan', 'kemiringan'] as $sensor) {
            $hasil[$sensor] = ['waspada' => (float) $d["{$sensor}_waspada"], 'validasi' => (float) $d["{$sensor}_validasi"]];
        }

        return $hasil;
    }
}
