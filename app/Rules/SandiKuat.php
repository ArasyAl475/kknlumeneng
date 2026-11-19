<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Satu aturan kata sandi admin untuk seeder, ganti kata sandi, dan lupa kata sandi:
 * sandi yang ditolak seeder di server juga tidak boleh bisa dipasang lewat web.
 */
class SandiKuat implements ValidationRule
{
    public const PESAN = 'Kata sandi minimal 12 karakter, berisi huruf dan angka, dan tidak memakai kata umum (password, sandi, admin, lumeneng, qwerty, 12345).';

    public static function memenuhi(string $sandi): bool
    {
        return mb_strlen($sandi) >= 12
            && preg_match('/\pL/u', $sandi) === 1
            && preg_match('/\d/', $sandi) === 1
            && preg_match('/password|sandi|admin|lumeneng|qwerty|12345/i', $sandi) !== 1;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::memenuhi($value)) {
            $fail(self::PESAN);
        }
    }
}
