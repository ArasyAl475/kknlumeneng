<?php

namespace App\Services\Toko;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class FormatHasil
{
    public static function kg(float|string $nilai): string
    {
        return number_format((float) $nilai, 2, ',', '.').' kg';
    }

    public static function rupiah(int $nilai): string
    {
        return 'Rp '.number_format($nilai, 0, ',', '.');
    }

    public static function persen(float $nilai): string
    {
        return number_format($nilai, 1, ',', '.').'%';
    }

    public static function hari(CarbonInterface $tanggal): string
    {
        return $tanggal->translatedFormat('l, d-m-Y');
    }

    public static function tanggal(string $tanggal): string
    {
        return Carbon::parse($tanggal)->format('d-m-Y');
    }
}
