<?php

namespace App\Console\Commands\Toko;

use App\Services\Toko\GoogleSheets;
use App\Services\Toko\PenyiapSheetHasil;
use Illuminate\Console\Command;
use Throwable;

class SiapkanSheetsCommand extends Command
{
    protected $signature = 'toko:sheets-siapkan';

    protected $description = 'Menyiapkan Google Sheet (tab, judul kolom, format), lalu mengisinya dengan catatan yang ada';

    public function handle(GoogleSheets $sheets, PenyiapSheetHasil $penyiap): int
    {
        if (! $sheets->siap()) {
            $this->error('Belum siap: set TOKO_SHEETS_AKTIF=true, TOKO_SHEETS_ID, dan TOKO_SHEETS_KUNCI di .env (lihat docs/toko/SINKRON_SHEETS.md).');

            return self::FAILURE;
        }

        try {
            $penyiap->siapkan();
        } catch (Throwable $e) {
            $this->error('Gagal menyiapkan Sheet: '.$e->getMessage());
            $this->line('Pastikan Sheet sudah dibagikan ke email akun layanan sebagai Editor dan Google Sheets API sudah diaktifkan.');

            return self::FAILURE;
        }

        $this->info('Tab dan judul kolom siap. Mengisi Sheet dengan catatan yang sudah ada...');

        return $this->call('toko:sheets-sinkron');
    }
}
