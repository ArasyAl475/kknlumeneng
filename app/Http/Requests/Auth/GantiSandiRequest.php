<?php

namespace App\Http\Requests\Auth;

use App\Rules\SandiKuat;
use Illuminate\Foundation\Http\FormRequest;

class GantiSandiRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Sandi lama wajib: sesi yang tertinggal terbuka di HP orang lain tidak cukup untuk mengambil alih akun.
            'sandi_lama' => ['required', 'string', 'current_password:web'],
            // Batas 72: bcrypt mengabaikan karakter sesudahnya, jadi sandi lebih panjang menipu pemiliknya.
            'password' => ['required', 'string', 'max:72', 'confirmed', 'different:sandi_lama', new SandiKuat],
        ];
    }

    public function attributes(): array
    {
        return [
            'sandi_lama' => 'kata sandi lama',
            'password' => 'kata sandi baru',
        ];
    }

    public function messages(): array
    {
        return [
            'sandi_lama.current_password' => 'Kata sandi lama salah.',
            'password.confirmed' => 'Ulangi kata sandi baru dengan isian yang sama persis.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi lama.',
        ];
    }
}
