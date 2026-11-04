<?php

namespace App\Services\Ews;

use App\Models\Ews\Pembacaan;
use App\Models\Ews\Pengaturan;
use App\Models\Ews\Wilayah;
use Illuminate\Support\Facades\Cache;

class DataGrafik
{
    // detik = rentang keseluruhan, kelompok = lebar satu titik di grafik.
    public const RENTANG = [
        '1j' => ['detik' => 3600, 'kelompok' => 60],
        '24j' => ['detik' => 86400, 'kelompok' => 600],
        '7h' => ['detik' => 604800, 'kelompok' => 3600],
    ];

    /**
     * @return array<string, mixed>
     */
    public function ambil(Wilayah $wilayah, string $sensor, string $rentang): array
    {
        return Cache::remember(
            "ews:grafik:{$wilayah->id}:{$sensor}:{$rentang}",
            30,
            fn () => $this->bangun($wilayah, $sensor, $rentang),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function bangun(Wilayah $wilayah, string $sensor, string $rentang): array
    {
        ['detik' => $detik, 'kelompok' => $kelompok] = self::RENTANG[$rentang];

        $baris = Pembacaan::query()->toBase()
            ->where('wilayah_id', $wilayah->id)
            ->where('created_at', '>=', now()->subSeconds($detik))
            ->whereNotNull($sensor)
            ->orderBy('created_at')
            ->get(['created_at', $sensor]);

        // Nilai tertinggi tiap kelompok, bukan rata-rata: puncak hujan singkat tidak boleh
        // tersamarkan dari grafik peringatan dini.
        $puncak = [];
        foreach ($baris as $b) {
            $slot = (int) floor(strtotime($b->created_at) / $kelompok);
            $nilai = PenilaianStatus::nilaiDinilai($sensor, (float) $b->{$sensor});
            $puncak[$slot] = max($puncak[$slot] ?? $nilai, $nilai);
        }

        $titik = [];
        $slotSebelum = null;
        foreach ($puncak as $slot => $nilai) {
            // Celah kiriman ditandai null agar garis terputus, tidak menggambar data yang tidak ada.
            if ($slotSebelum !== null && $slot - $slotSebelum > 3) {
                $titik[] = ['t' => ($slotSebelum + 1) * $kelompok * 1000, 'y' => null];
            }
            $titik[] = ['t' => $slot * $kelompok * 1000, 'y' => round($nilai, 2)];
            $slotSebelum = $slot;
        }

        return [
            'sensor' => Sensor::DAFTAR[$sensor]['nama'],
            'satuan' => trim(Sensor::DAFTAR[$sensor]['satuan']),
            'wilayah' => $wilayah->nama,
            'dari' => (now()->getTimestamp() - $detik) * 1000,
            'sampai' => now()->getTimestamp() * 1000,
            'ambang' => Pengaturan::ambang()[$sensor] ?? null,
            'titik' => $titik,
        ];
    }
}
