<?php

namespace Database\Seeders;

use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use App\Models\Toko\PenjualanJamur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data contoh setahun terakhir untuk mencoba grafik Hari / Bulan / Tahun di /toko/hasil.
 * Jalankan dengan: php artisan db:seed --class=TokoContohHasilSeeder
 * Sengaja TIDAK dipanggil dari DatabaseSeeder, menolak jalan di produksi, dan menolak
 * jalan bila sudah ada catatan asli supaya tidak bercampur.
 */
class TokoContohHasilSeeder extends Seeder
{
    private const TEMPAT = ['Krajan', 'Kebun Atas', 'Lereng Timur'];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('Data contoh tidak boleh dibuat di produksi.');

            return;
        }
        if (PanenKopi::query()->exists() || PanenJamur::query()->exists() || PenjualanJamur::query()->exists()) {
            $this->command->warn('Sudah ada catatan Toko. Seeder dibatalkan agar data asli tidak bercampur.');

            return;
        }

        // Angka acak selalu sama, jadi contoh bisa dibandingkan antar-komputer.
        mt_srand(2026);

        $akhir = now()->startOfDay();
        $awal = $akhir->copy()->subYear()->addDay();

        $this->panenKopi($awal, $akhir);
        $this->panenJamur($awal, $akhir);
        $this->penjualanJamur($awal, $akhir);

        $this->command->info('Data contoh dibuat: '.PanenKopi::count().' panen kopi, '.PanenJamur::count().' panen jamur, '.PenjualanJamur::count().' penjualan.');
    }

    private function panenKopi(Carbon $awal, Carbon $akhir): void
    {
        // Peluang panen per hari menurut bulan: musim petik Mei sampai Oktober, puncaknya Juli dan Agustus.
        $musim = [1 => 0.02, 2 => 0.02, 3 => 0.03, 4 => 0.08, 5 => 0.25, 6 => 0.4, 7 => 0.6, 8 => 0.6, 9 => 0.4, 10 => 0.2, 11 => 0.06, 12 => 0.03];

        for ($hari = $awal->copy(); $hari->lte($akhir); $hari->addDay()) {
            if (mt_rand(0, 999) / 1000 > $musim[$hari->month]) {
                continue;
            }
            foreach ((array) array_rand(self::TEMPAT, mt_rand(1, 2)) as $tempat) {
                $jenis = $hari->month >= 7 && mt_rand(0, 2) === 0 ? 'Arabika' : 'Robusta';
                $kg = mt_rand(3500, 21000) / 100 * (0.6 + $musim[$hari->month]);
                PanenKopi::query()->create([
                    'tanggal_panen' => $hari->format('Y-m-d'),
                    'jenis_kopi' => $jenis,
                    'tempat_panen' => self::TEMPAT[(int) $tempat],
                    'jumlah_kg' => round($kg, 2),
                    // Cangkang kira-kira seperlima sampai sepertiga dari panen.
                    'limbah_kg' => round($kg * mt_rand(20, 32) / 100, 2),
                ]);
            }
        }
    }

    private function panenJamur(Carbon $awal, Carbon $akhir): void
    {
        // Satu catatan per kelompok tiap pekan. Baglog dengan cangkang kopi dibuat sedikit lebih produktif.
        for ($hari = $awal->copy()->next(Carbon::SATURDAY); $hari->lte($akhir); $hari->addWeek()) {
            $baglog = mt_rand(80, 150);
            foreach ([true, false] as $denganCangkang) {
                $perBaglog = ($denganCangkang ? 0.52 : 0.41) * (mt_rand(88, 112) / 100);
                PanenJamur::query()->create([
                    'tanggal_panen' => $hari->format('Y-m-d'),
                    'jumlah_kg' => round($baglog * $perBaglog, 2),
                    'jumlah_baglog' => $baglog,
                    'biaya_baglog_rupiah' => $baglog * ($denganCangkang ? 5200 : 6100),
                    'dengan_cangkang_kopi' => $denganCangkang,
                ]);
            }
        }
    }

    private function penjualanJamur(Carbon $awal, Carbon $akhir): void
    {
        // Tiga bulan pertama dibiarkan tanpa media tanam, seperti catatan lama yang nyata.
        $mulaiMedia = $awal->copy()->addMonths(3);

        for ($hari = $awal->copy()->next(Carbon::TUESDAY); $hari->lte($akhir); $hari->addDays(mt_rand(2, 4))) {
            $kg = mt_rand(40, 260) / 10;
            PenjualanJamur::query()->create([
                'tanggal_penjualan' => $hari->format('Y-m-d'),
                'jumlah_kg' => $kg,
                'dengan_cangkang_kopi' => $hari->gte($mulaiMedia) ? mt_rand(0, 1) === 1 : null,
                'total_rupiah' => (int) round($kg * mt_rand(24, 32) * 1000, -2),
            ]);
        }
    }
}
