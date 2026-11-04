<?php

namespace App\Services\Ews;

use App\Jobs\Ews\KirimPesanTelegram;
use App\Models\Ews\Pembacaan;
use App\Models\Ews\Wilayah;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PemberitahuTelegram
{
    public function perluValidasi(Pembacaan $pembacaan): void
    {
        // Anti-spam per wilayah: setelah admin menolak dan jeda validasi lewat, laporan baru
        // tetap masuk antrean admin, tetapi Telegram tidak berbunyi lebih sering dari cooldown.
        $jeda = (int) config('ews.telegram.cooldown_menit');
        $kunci = "ews:telegram-validasi:{$pembacaan->wilayah_id}";

        if ($jeda > 0 && ! Cache::add($kunci, true, now()->addMinutes($jeda))) {
            return;
        }

        $this->kirim(
            "PERLU VALIDASI ADMIN\nNilai sensor melewati batas validasi",
            $pembacaan,
            url('/admin/ews/validasi'),
        );
    }

    public function awasKabelPutus(Pembacaan $pembacaan): void
    {
        $this->kirim("STATUS AWAS (otomatis)\nPenyebab: kabel EWS putus", $pembacaan, url('/ews'));
    }

    public function awasDisetujui(Pembacaan $pembacaan): void
    {
        $this->kirim("STATUS AWAS\nPenyebab: dinyatakan admin setelah validasi", $pembacaan, url('/ews'));
    }

    public function alatOffline(Wilayah $wilayah, CarbonInterface $terakhir): void
    {
        $this->antre(implode("\n", [
            'ALAT TIDAK MENGIRIM DATA',
            "Lokasi: {$wilayah->nama}",
            'Kiriman terakhir: '.$terakhir->format('d-m-Y H:i').' WIB',
            url('/admin/ews/alat'),
        ]));
    }

    private function kirim(string $judul, Pembacaan $pembacaan, string $tautan): void
    {
        $baris = ['PERINGATAN DINI LONGSOR', $judul, 'Lokasi: '.$pembacaan->wilayah->nama];

        foreach ([...PenilaianStatus::SENSOR_DINILAI, 'kelembapan_udara'] as $sensor) {
            if ($pembacaan->{$sensor} !== null) {
                $baris[] = PenilaianStatus::teksSensor($sensor, $pembacaan->{$sensor});
            }
        }

        if ($pembacaan->pemicu) {
            $baris[] = 'Pemicu: '.$pembacaan->pemicu;
        }

        $baris[] = 'Waktu: '.$pembacaan->created_at->format('d-m-Y H:i').' WIB';
        $baris[] = $tautan;

        $this->antre(implode("\n", $baris));
    }

    private function antre(string $pesan): void
    {
        if (! config('ews.telegram.token') || ! config('ews.telegram.chat_id')) {
            Log::warning('Telegram EWS belum diatur: TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID kosong.');

            return;
        }

        // Dikirim setelah respons agar alat tidak ikut menunggu Telegram, dan tanpa
        // ketergantungan pada queue worker karena hosting produksi belum diputuskan.
        KirimPesanTelegram::dispatchAfterResponse($pesan);
    }
}
