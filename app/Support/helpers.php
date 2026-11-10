<?php

use App\Services\Desain\GambarSitus;

/*
 * Pembantu gambar tata letak untuk semua tampilan. Contoh di Blade:
 *   <img src="{{ site_image('beranda.hero') }}" alt="{{ site_image_alt('beranda.hero') }}">
 * atau cukup <x-gambar-situs kunci="beranda.hero" class="..." />.
 * Bila slot kosong atau berkasnya hilang, gambar bawaan dipakai otomatis.
 */

if (! function_exists('site_image')) {
    function site_image(string $kunci): string
    {
        return app(GambarSitus::class)->url($kunci);
    }
}

if (! function_exists('site_image_alt')) {
    function site_image_alt(string $kunci): string
    {
        return app(GambarSitus::class)->alt($kunci);
    }
}
