<?php

namespace App\Services\Edukasi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PembuatSlug
{
    /**
     * Slug adalah bagian alamat halaman, misalnya /edukasi/cara-membuat-kompos.
     *
     * @param  class-string<Model>  $kelasModel
     */
    public static function untuk(string $kelasModel, string $teks, string $cadangan): string
    {
        // Judul yang seluruhnya simbol menghasilkan slug kosong, padahal alamat tetap harus ada.
        $dasar = rtrim(Str::substr(Str::slug($teks), 0, 150), '-') ?: $cadangan;
        $slug = $dasar;
        $nomor = 2;

        while ($kelasModel::query()->where('slug', $slug)->exists()) {
            $slug = "{$dasar}-{$nomor}";
            $nomor++;
        }

        return $slug;
    }
}
