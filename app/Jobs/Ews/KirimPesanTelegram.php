<?php

namespace App\Jobs\Ews;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class KirimPesanTelegram
{
    use Dispatchable;

    public function __construct(public string $pesan) {}

    public function handle(): void
    {
        $token = (string) config('ews.telegram.token');

        try {
            $respons = Http::connectTimeout(3)->timeout(5)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => config('ews.telegram.chat_id'),
                    'text' => $this->pesan,
                ]);

            if ($respons->failed()) {
                Log::error('Telegram EWS ditolak server.', ['status' => $respons->status()]);
            }
        } catch (Throwable $galat) {
            // Pesan galat HTTP memuat alamat lengkap, yang berisi token; harus disamarkan sebelum dicatat.
            Log::error('Telegram EWS gagal: '.str_replace($token, '***', $galat->getMessage()));
        }
    }
}
