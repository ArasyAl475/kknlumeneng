<?php

namespace App\Console\Commands\Toko;

use App\Services\Toko\GoogleSheets;
use App\Services\Toko\ImporExcelHasil;
use App\Services\Toko\SinkronSheetHasil;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SinkronSheetsCommand extends Command
{
    protected $signature = 'toko:sheets-sinkron';

    protected $description = 'Menyinkronkan catatan hasil panen dengan Google Sheets (dijalankan penjadwal tiap menit)';

    public function handle(GoogleSheets $sheets, SinkronSheetHasil $sinkron): int
    {
        // Saklar mati = bukan galat: penjadwal tetap boleh memanggil perintah ini.
        if (! config('toko_sheets.aktif')) {
            return self::SUCCESS;
        }

        if (! $sheets->siap()) {
            $this->error('Sinkron Google Sheets belum lengkap: isi TOKO_SHEETS_ID dan TOKO_SHEETS_KUNCI di .env (lihat docs/toko/SINKRON_SHEETS.md).');

            return self::FAILURE;
        }

        try {
            $hasil = $sinkron->sinkronkan();
        } catch (Throwable $e) {
            // Hanya pesan yang dicatat; kunci dan token tidak pernah ikut tercetak.
            Log::error('Sinkron Google Sheets gagal: '.$e->getMessage());
            $this->error('Sinkron gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($hasil === null) {
            $this->line('Putaran sinkron lain masih berjalan; dilewati.');

            return self::SUCCESS;
        }

        foreach ($hasil as $jenis => $h) {
            if (isset($h['galat'])) {
                $this->warn(ImporExcelHasil::JENIS[$jenis]['label'].': '.$h['galat']);

                continue;
            }
            $this->line(sprintf(
                '%s: %d baru, %d diubah dari Sheet, %d diubah dari sistem, %d konflik, %d baris salah, %d dikembalikan.',
                ImporExcelHasil::JENIS[$jenis]['label'], $h['dibuat'], $h['diperbarui'], $h['dari_sistem'], $h['konflik'], $h['salah'], $h['dikembalikan'],
            ));
        }

        return self::SUCCESS;
    }
}
