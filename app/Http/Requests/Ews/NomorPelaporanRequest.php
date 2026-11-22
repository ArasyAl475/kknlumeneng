<?php

namespace App\Http\Requests\Ews;

use App\Services\Ews\NomorPelaporan;
use Illuminate\Foundation\Http\FormRequest;

class NomorPelaporanRequest extends FormRequest
{
    // Dirapikan sebelum divalidasi, supaya admin boleh menulis nomor dengan gaya apa pun.
    // Bila gagal dirapikan, nilai asli dibiarkan agar aturan regex menolaknya.
    protected function prepareForValidation(): void
    {
        $rapi = NomorPelaporan::rapikan($this->input('nomor_wa'));

        if ($rapi !== null) {
            $this->merge(['nomor_wa' => $rapi]);
        }
    }

    public function rules(): array
    {
        return [
            'nomor_wa' => ['required', 'string', 'regex:'.NomorPelaporan::POLA],
        ];
    }

    public function messages(): array
    {
        return [
            'nomor_wa.required' => 'Nomor WhatsApp wajib diisi.',
            'nomor_wa.regex' => 'Nomor tidak dikenali. Tulis nomor HP Indonesia, misalnya 0812 3456 7890.',
        ];
    }
}
