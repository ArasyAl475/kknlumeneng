<?php

namespace App\Services\Landing;

use App\Services\Ews\DataDasbor;
use Illuminate\Support\Facades\Log;
use Throwable;

class RingkasanEws
{
    /**
     * Ringkasan status terburuk untuk halaman depan, diambil dari layanan yang sama dengan dashboard.
     * Halaman depan harus tetap tampil walau modul EWS mati atau basis datanya bermasalah.
     *
     * @return array{status: string, warna: string, lokasi: ?string, pemicu: ?string, waktu: ?string, ada_offline: bool, jumlah_wilayah: int, jumlah_offline: int}|null
     */
    public function ambil(): ?array
    {
        if (! config('modul.ews.aktif')) {
            return null;
        }

        try {
            return app(DataDasbor::class)->ringkasan();
        } catch (Throwable $galat) {
            Log::warning('Ringkasan EWS gagal dimuat: '.$galat->getMessage());

            return null;
        }
    }
}
