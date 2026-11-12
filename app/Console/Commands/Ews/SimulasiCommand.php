<?php

namespace App\Console\Commands\Ews;

use App\Models\Ews\Pembacaan;
use App\Models\Ews\Pengaturan;
use App\Models\Ews\Wilayah;
use App\Services\Ews\PenerimaPembacaan;
use App\Services\Ews\PenilaianStatus;
use Illuminate\Console\Command;

class SimulasiCommand extends Command
{
    protected $signature = 'ews:simulasi
        {--skenario=acak : acak, aman, waspada, validasi, atau kabel}
        {--wilayah= : ID alat wilayah (kosong = semua)}
        {--riwayat=0 : isi riwayat sekian jam ke belakang, tiap 10 menit, tanpa memicu pemberitahuan}';

    protected $description = 'Membuat data sensor buatan untuk demo dan pengembangan (ditolak di produksi)';

    public function handle(PenerimaPembacaan $penerima): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('Perintah ini tidak boleh dijalankan di produksi: data buatan bisa disangka kondisi lereng sungguhan.');

            return self::FAILURE;
        }

        $skenario = (string) $this->option('skenario');
        if (! in_array($skenario, ['acak', 'aman', 'waspada', 'validasi', 'kabel'], true)) {
            $this->error('Skenario tidak dikenal.');

            return self::INVALID;
        }

        $daftar = Wilayah::query()
            ->when($this->option('wilayah'), fn ($q, $id) => $q->where('id_alat', $id))
            ->get();

        if ($daftar->isEmpty()) {
            $this->error('Belum ada wilayah. Jalankan: php artisan db:seed');

            return self::FAILURE;
        }

        foreach ($daftar as $wilayah) {
            $this->isiRiwayat($wilayah, (int) $this->option('riwayat'));

            $pembacaan = $penerima->terima($wilayah, $this->bacaan($skenario), $skenario === 'kabel');
            $this->line("{$wilayah->nama}: {$pembacaan->status}".($pembacaan->pemicu ? " ({$pembacaan->pemicu})" : ''));
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, float>
     */
    private function bacaan(string $skenario): array
    {
        $acak = fn (float $min, float $maks) => round($min + lcg_value() * ($maks - $min), 2);

        $bacaan = [
            'kelembapan_tanah' => $acak(25, 45),
            'intensitas_hujan' => $acak(0, 25),
            'kemiringan' => $acak(-2, 3),
            'kelembapan_udara' => $acak(65, 95),
        ];

        if ($skenario === 'acak') {
            $skenario = lcg_value() < 0.85 ? 'aman' : 'waspada';
        }

        return match ($skenario) {
            'waspada' => ['kelembapan_tanah' => $acak(52, 75), 'intensitas_hujan' => $acak(42, 65)] + $bacaan,
            'validasi' => ['kelembapan_tanah' => $acak(82, 96), 'intensitas_hujan' => $acak(72, 95), 'kemiringan' => $acak(3, 8)] + $bacaan,
            default => $bacaan,
        };
    }

    // Riwayat untuk grafik ditulis langsung, tanpa lewat penerima, supaya tidak memicu validasi.
    private function isiRiwayat(Wilayah $wilayah, int $jam): void
    {
        if ($jam <= 0) {
            return;
        }

        $ambang = Pengaturan::ambang();

        for ($menit = $jam * 60; $menit > 0; $menit -= 10) {
            // Mengikuti kurva hujan harian agar grafik demo terlihat wajar, bukan derau acak.
            $gelombang = (sin($menit / 180) + 1) / 2;
            $bacaan = [
                'kelembapan_tanah' => round(30 + $gelombang * 32 + lcg_value() * 3, 2),
                'intensitas_hujan' => round(max(0, $gelombang * 45 - 5 + lcg_value() * 4), 2),
                'kemiringan' => round(0.5 + $gelombang * 3 + lcg_value() * 0.4, 2),
                'kelembapan_udara' => round(68 + $gelombang * 24, 2),
            ];
            $hasil = PenilaianStatus::nilai($bacaan, $ambang);

            (new Pembacaan)->forceFill($bacaan + [
                'wilayah_id' => $wilayah->id,
                'status' => $hasil['status'],
                'warna' => $hasil['warna'],
                'pemicu' => $hasil['pemicu'],
                'created_at' => now()->subMinutes($menit),
            ])->save();
        }
    }
}
