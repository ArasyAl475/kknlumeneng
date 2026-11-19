<?php

namespace App\Services\Ews;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien tipis Firebase Cloud Messaging HTTP v1, masuk memakai akun layanan (service account).
 *
 * Sengaja tanpa kreait/laravel-firebase (puluhan paket, dan mengubah composer.json yang dipakai bersama):
 * cukup menandatangani JWT lalu memakai HTTP client Laravel, sama seperti App\Services\Toko\GoogleSheets.
 * Alamat penukar token ditulis tetap di sini, bukan dibaca dari berkas kunci, supaya berkas kunci tidak bisa
 * mengarahkan sistem ke tempat lain. Token akses dan isi kunci tidak pernah dicatat ke log.
 */
class PushFcm
{
    private const ALAMAT_TOKEN = 'https://oauth2.googleapis.com/token';

    private const LINGKUP = 'https://www.googleapis.com/auth/firebase.messaging';

    private const KUNCI_CACHE = 'ews:fcm:token';

    public function siap(): bool
    {
        return $this->masalah() === null;
    }

    /**
     * Alasan push belum bisa dipakai, untuk pesan yang jelas di perintah uji. null = siap.
     */
    public function masalah(): ?string
    {
        if (! config('ews.push.aktif')) {
            return 'EWS_PUSH_AKTIF di .env belum true.';
        }

        $berkas = (string) config('ews.push.kredensial');
        if ($berkas === '') {
            return 'FIREBASE_CREDENTIALS di .env masih kosong.';
        }
        if (! is_readable($berkas)) {
            return 'Berkas kunci Firebase tidak ditemukan atau tidak bisa dibaca. Periksa jalur FIREBASE_CREDENTIALS.';
        }

        $kunci = json_decode((string) file_get_contents($berkas), true);
        if (! is_array($kunci) || ! filled($kunci['project_id'] ?? null) || ! filled($kunci['client_email'] ?? null) || ! filled($kunci['private_key'] ?? null)) {
            return 'Berkas kunci Firebase bukan kunci akun layanan yang lengkap (butuh project_id, client_email, private_key).';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $pesan  isi "message" FCM v1 (topic, notification, data, android)
     * @param  bool  $hanyaPeriksa  true = Firebase hanya memeriksa kunci dan isi pesan, tidak ada HP yang menerima
     * @return string nama pesan dari Firebase, mis. projects/x/messages/123
     */
    public function kirim(array $pesan, bool $hanyaPeriksa = false): string
    {
        $kunci = $this->bacaKunci();

        $balasan = Http::withToken($this->token($kunci))
            ->timeout((int) config('ews.push.timeout_detik', 5))
            ->connectTimeout(3)
            ->acceptJson()
            ->post(
                'https://fcm.googleapis.com/v1/projects/'.rawurlencode($kunci['project_id']).'/messages:send',
                ['message' => $pesan] + ($hanyaPeriksa ? ['validate_only' => true] : []),
            );

        if ($balasan->status() === 401) {
            // Token mungkin dicabut lebih awal; minta baru pada percobaan berikutnya.
            Cache::forget(self::KUNCI_CACHE);
        }

        $balasan->throw();

        return (string) $balasan->json('name');
    }

    /**
     * @param  array{client_email: string, private_key: string}  $kunci
     */
    private function token(array $kunci): string
    {
        // Token Google berlaku 1 jam; disimpan lebih singkat agar tidak pernah dipakai saat hampir habis.
        return Cache::remember(self::KUNCI_CACHE, 3000, function () use ($kunci): string {
            $sekarang = time();
            $kepala = $this->base64Url((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $isi = $this->base64Url((string) json_encode([
                'iss' => $kunci['client_email'],
                'scope' => self::LINGKUP,
                'aud' => self::ALAMAT_TOKEN,
                'iat' => $sekarang,
                'exp' => $sekarang + 3600,
            ]));

            if (! openssl_sign("{$kepala}.{$isi}", $tandatangan, $kunci['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Kunci akun layanan Firebase tidak bisa dipakai menandatangani. Periksa isi berkas kunci.');
            }

            $token = Http::asForm()->timeout((int) config('ews.push.timeout_detik', 5))->connectTimeout(3)->post(self::ALAMAT_TOKEN, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => "{$kepala}.{$isi}.".$this->base64Url($tandatangan),
            ])->throw()->json('access_token');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('Google tidak memberi token akses Firebase.');
            }

            return $token;
        });
    }

    /**
     * @return array{project_id: string, client_email: string, private_key: string}
     */
    private function bacaKunci(): array
    {
        $masalah = $this->masalah();
        if ($masalah !== null) {
            throw new RuntimeException($masalah);
        }

        return json_decode((string) file_get_contents((string) config('ews.push.kredensial')), true);
    }

    private function base64Url(string $teks): string
    {
        return rtrim(strtr(base64_encode($teks), '+/', '-_'), '=');
    }
}
