<?php

namespace App\Services\Toko;

use Illuminate\Support\Facades\Cache;

/**
 * Sidik singkat seluruh catatan hasil panen. Berubah setiap kali ada catatan dibuat, diubah, atau dihapus,
 * baik dari panel admin maupun dari Google Sheets.
 *
 * Halaman menanyakan sidik ini berkala (respons beberapa bita) dan baru mengambil isi halaman bila sidiknya
 * berbeda, jadi tetap ringan di sinyal lemah.
 */
class VersiHasil
{
    private const KUNCI = 'toko_hasil.versi';

    // Banyak HP bertanya tiap 5 detik; database cukup dihitung sekali per beberapa detik.
    private const DETIK_CACHE = 3;

    public function sekarang(): string
    {
        return Cache::remember(self::KUNCI, self::DETIK_CACHE, function (): string {
            $bagian = [];
            foreach (IsianHasilPanen::JENIS as $jenis => $def) {
                // Jumlah baris menangkap penghapusan; waktu ubah terakhir menangkap catatan baru dan yang diubah.
                $r = $def['model']::query()->toBase()->selectRaw('count(*) as jumlah, max(updated_at) as terakhir')->first();
                $bagian[] = "{$jenis}:{$r->jumlah}:{$r->terakhir}";
            }

            return substr(sha1(implode('|', $bagian)), 0, 16);
        });
    }
}
