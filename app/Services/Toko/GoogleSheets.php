<?php

namespace App\Services\Toko;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien tipis untuk Google Sheets API v4, masuk memakai akun layanan (service account).
 *
 * Sengaja tanpa pustaka google/apiclient (besar, dan mengubah composer.json yang dipakai bersama):
 * cukup menandatangani JWT lalu memakai HTTP client Laravel. Alamat penukar token ditulis tetap di
 * sini, bukan dibaca dari berkas kunci, supaya berkas kunci tidak bisa mengarahkan sistem ke tempat lain.
 * Token akses dan isi kunci tidak pernah dicatat ke log.
 */
class GoogleSheets
{
    private const ALAMAT_TOKEN = 'https://oauth2.googleapis.com/token';

    private const ALAMAT_API = 'https://sheets.googleapis.com/v4/spreadsheets/';

    private const LINGKUP = 'https://www.googleapis.com/auth/spreadsheets';

    private const KUNCI_CACHE = 'toko_sheets.token';

    public function siap(): bool
    {
        return (bool) config('toko_sheets.aktif')
            && filled(config('toko_sheets.spreadsheet_id'))
            && filled(config('toko_sheets.kunci_json'))
            && is_readable((string) config('toko_sheets.kunci_json'));
    }

    /**
     * @param  array<int, string>  $rentang  notasi A1, mis. "'Panen kopi'!A1:H"
     * @return array<int, array<int, array<int, mixed>>> baris per rentang, urutan sama dengan $rentang
     */
    public function bacaTab(array $rentang): array
    {
        $query = 'majorDimension=ROWS&valueRenderOption=UNFORMATTED_VALUE&'
            .implode('&', array_map(fn (string $r) => 'ranges='.rawurlencode($r), $rentang));

        $json = $this->klien()->get($this->alamat('/values:batchGet?'.$query))->throw()->json();

        return array_map(fn (array $vr) => $vr['values'] ?? [], $json['valueRanges'] ?? []);
    }

    /**
     * @param  array<int, array{range: string, values: array<int, array<int, mixed>>}>  $data
     */
    public function tulis(array $data): void
    {
        $this->klien()->post($this->alamat('/values:batchUpdate'), [
            // RAW: isian ditulis apa adanya, tidak ditebak-tebak jadi tanggal/rumus oleh Sheets.
            'valueInputOption' => 'RAW',
            'data' => $data,
        ])->throw();
    }

    /**
     * @return array<string, int> judul tab => sheetId
     */
    public function lembar(): array
    {
        $json = $this->klien()->get($this->alamat('?fields=sheets.properties(sheetId,title)'))->throw()->json();

        $hasil = [];
        foreach ($json['sheets'] ?? [] as $lembar) {
            $hasil[$lembar['properties']['title']] = (int) $lembar['properties']['sheetId'];
        }

        return $hasil;
    }

    /**
     * @param  array<int, array<string, mixed>>  $permintaan
     */
    public function ubahStruktur(array $permintaan): void
    {
        $this->klien()->post($this->alamat(':batchUpdate'), ['requests' => $permintaan])->throw();
    }

    private function alamat(string $akhiran): string
    {
        return self::ALAMAT_API.rawurlencode((string) config('toko_sheets.spreadsheet_id')).$akhiran;
    }

    private function klien(): PendingRequest
    {
        return Http::withToken($this->token())->timeout((int) config('toko_sheets.timeout_detik'))->acceptJson();
    }

    private function token(): string
    {
        // Token Google berlaku 1 jam; disimpan lebih singkat agar tidak pernah dipakai saat hampir habis.
        return Cache::remember(self::KUNCI_CACHE, 3000, function (): string {
            $kunci = $this->bacaKunci();
            $sekarang = time();

            $kepala = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $isi = $this->base64Url(json_encode([
                'iss' => $kunci['client_email'],
                'scope' => self::LINGKUP,
                'aud' => self::ALAMAT_TOKEN,
                'iat' => $sekarang,
                'exp' => $sekarang + 3600,
            ]));

            if (! openssl_sign("{$kepala}.{$isi}", $tandatangan, $kunci['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Kunci akun layanan Google tidak bisa dipakai menandatangani. Periksa isi berkas kunci.');
            }

            $respons = Http::asForm()->timeout((int) config('toko_sheets.timeout_detik'))->post(self::ALAMAT_TOKEN, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => "{$kepala}.{$isi}.".$this->base64Url($tandatangan),
            ])->throw();

            return (string) $respons->json('access_token');
        });
    }

    /**
     * @return array{client_email: string, private_key: string}
     */
    private function bacaKunci(): array
    {
        $jalur = (string) config('toko_sheets.kunci_json');
        $kunci = is_readable($jalur) ? json_decode((string) file_get_contents($jalur), true) : null;

        if (! is_array($kunci) || blank($kunci['client_email'] ?? null) || blank($kunci['private_key'] ?? null)) {
            throw new RuntimeException('Berkas kunci Google tidak ditemukan atau isinya bukan kunci akun layanan (TOKO_SHEETS_KUNCI).');
        }

        return ['client_email' => $kunci['client_email'], 'private_key' => $kunci['private_key']];
    }

    private function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
