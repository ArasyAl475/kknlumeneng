<?php

namespace App\Http\Controllers\Concerns;

use App\Models\SensorData;
use App\Support\PenilaianStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait KirimTelegram
{
    // Mengirim pesan ke Telegram lewat HTTP Client bawaan Laravel. Mengembalikan true jika berhasil.
    // $jenis: 'awas_kabel' (kabel putus), 'awas_validasi' (admin menyetujui), 'validasi' (perlu dicek admin)
    // $data (opsional): bacaan sensor yang memicu pesan, supaya semua sensor ikut ditampilkan.
    public function sendTelegramNotification($lokasi, $kelembapan, string $jenis = 'awas_kabel', ?SensorData $data = null): bool
    {
        $token  = config('ews.telegram_token');
        $chatId = config('ews.telegram_chat_id');

        if (! $token || ! $chatId) {
            Log::warning('Telegram: TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID belum diisi di .env');
            return false;
        }

        // Anti-spam: alat asli mengirim data terus-menerus, jangan minta validasi setiap detik.
        $jeda = (int) config('ews.telegram_cooldown_menit');
        if ($jenis === 'validasi' && $jeda > 0 && ! Cache::add('ews_validasi_' . md5($lokasi), true, now()->addMinutes($jeda))) {
            Log::info("Telegram: pesan validasi {$lokasi} ditahan (masih dalam jeda {$jeda} menit)");
            return false;
        }

        $judul = match ($jenis) {
            'awas_kabel'    => "STATUS AWAS (otomatis)\nPenyebab: kabel EWS putus",
            'awas_validasi' => "STATUS AWAS\nPenyebab: dinyatakan admin setelah validasi",
            default         => "PERLU VALIDASI ADMIN\nNilai sensor melewati batas validasi",
        };
        $tautan = $jenis === 'validasi' ? route('admin.dashboard') : route('dashboard');

        // Bacaan sensor yang tersedia (yang kosong dilewati)
        $baris = [];
        $tanah = $data?->kelembapan ?? $kelembapan;
        if ($tanah !== null)                    $baris[] = 'Kelembapan tanah: ' . PenilaianStatus::angka($tanah) . '%';
        if ($data?->curah_hujan !== null)       $baris[] = 'Curah hujan: ' . PenilaianStatus::angka($data->curah_hujan) . ' mm/jam';
        if ($data?->kemiringan !== null)        $baris[] = 'Kemiringan: ' . PenilaianStatus::angka($data->kemiringan) . ' derajat';
        if ($data?->kelembapan_udara !== null)  $baris[] = 'Kelembapan udara: ' . PenilaianStatus::angka($data->kelembapan_udara) . '%';
        if ($data?->pemicu)                     $baris[] = 'Pemicu: ' . $data->pemicu;

        $pesan = "PERINGATAN DINI LONGSOR\n{$judul}\n"
               . "Lokasi: {$lokasi}\n" . implode("\n", $baris) . "\n"
               . 'Waktu: ' . now()->format('d-m-Y H:i:s') . "\n{$tautan}";

        try {
            // Alamat resmi API Telegram: https://api.telegram.org/bot<TOKEN>/sendMessage
            $respon = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => $pesan,
            ]);

            if ($respon->successful()) {
                return true;
            }

            Log::error('Telegram gagal', ['status' => $respon->status(), 'body' => $respon->body()]);
        } catch (\Throwable $e) {
            // Gagal kirim Telegram tidak boleh menggagalkan penyimpanan data
            Log::error('Telegram error: ' . $e->getMessage());
        }

        return false;
    }
}
