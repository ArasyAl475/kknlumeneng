<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class Modul
{
    /**
     * Semua modul beserta penanda 'tersedia'. Syarat rute ikut diperiksa agar menu tidak
     * pernah menjadi tautan mati ketika bendera .env menyala tetapi modulnya belum digabung.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function semua(): array
    {
        return collect(config('modul', []))
            ->map(fn (array $modul) => $modul + [
                'tersedia' => $modul['aktif'] && Route::has($modul['rute']),
            ])
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function tersedia(): array
    {
        return array_filter(self::semua(), fn (array $modul) => $modul['tersedia']);
    }
}
