<?php

namespace App\Http\Requests\Auth;

use App\Rules\SandiKuat;
use Illuminate\Foundation\Http\FormRequest;

class AturUlangSandiRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            // Batas 72: bcrypt mengabaikan karakter sesudahnya, jadi sandi lebih panjang menipu pemiliknya.
            'password' => ['required', 'string', 'max:72', 'confirmed', new SandiKuat],
        ];
    }

    public function attributes(): array
    {
        return ['password' => 'kata sandi baru'];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Tautan tidak lengkap. Buka lagi tautan dari email, atau minta tautan baru.',
            'password.confirmed' => 'Ulangi kata sandi baru dengan isian yang sama persis.',
        ];
    }
}
