<?php

namespace App\Services\Toko\Shopee;

use App\Models\Toko\Aktivitas;
use App\Models\Toko\TokenShopee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan, memperpanjang, dan memutus izin akses toko Shopee. Situs ini hanya melayani
 * satu toko desa, jadi menghubungkan toko lain menggantikan koneksi lama.
 */
class KoneksiShopee
{
    /** Token akses Shopee hidup ±4 jam; diperpanjang bila sisa umurnya di bawah ambang ini. */
    public const AMBANG_PERPANJANG_MENIT = 90;

    /** Ketentuan Shopee: refresh token berlaku 30 hari sejak diterbitkan. */
    public const UMUR_REFRESH_HARI = 30;

    public const HASIL_TIDAK_ADA = 'tidak_ada';

    public const HASIL_BELUM_PERLU = 'belum_perlu';

    public const HASIL_DIPERPANJANG = 'diperpanjang';

    public function __construct(private readonly KlienShopee $klien) {}

    public function siap(): bool
    {
        return $this->klien->siap();
    }

    public function tersimpan(): ?TokenShopee
    {
        return TokenShopee::query()->latest('id')->first();
    }

    public function hubungkan(string $kode, int $shopId, User $admin): TokenShopee
    {
        $token = $this->klien->tukarKode($kode, $shopId);
        $namaToko = $this->namaToko($token['access_token'], $shopId);

        $tersimpan = DB::transaction(function () use ($token, $shopId, $namaToko, $admin) {
            TokenShopee::query()->where('shop_id', '!=', $shopId)->delete();

            return TokenShopee::query()->updateOrCreate(['shop_id' => $shopId], [
                'nama_toko' => $namaToko,
                ...$this->isianToken($token),
                'terhubung_oleh' => $admin->id,
            ]);
        });

        Aktivitas::catat($admin, 'hubungkan_shopee', 'Menghubungkan toko Shopee '.($namaToko ?? "nomor {$shopId}").'.');

        return $tersimpan;
    }

    /**
     * @return self::HASIL_*
     */
    public function perpanjangBilaPerlu(bool $paksa = false): string
    {
        $token = $this->tersimpan();

        if (! $token) {
            return self::HASIL_TIDAK_ADA;
        }

        if (! $paksa && $token->akses_kedaluwarsa_pada->isAfter(now()->addMinutes(self::AMBANG_PERPANJANG_MENIT))) {
            return self::HASIL_BELUM_PERLU;
        }

        $token->update($this->isianToken($this->klien->perpanjangToken($token->refresh_token, $token->shop_id)));

        return self::HASIL_DIPERPANJANG;
    }

    public function putuskan(User $admin): void
    {
        $terhapus = TokenShopee::query()->delete();

        if ($terhapus > 0) {
            Aktivitas::catat($admin, 'putuskan_shopee', 'Memutus koneksi Shopee. Data produk yang sudah ada tetap tersimpan.');
        }
    }

    /**
     * @param  array{access_token: string, refresh_token: string, expire_in: int}  $token
     * @return array<string, mixed>
     */
    private function isianToken(array $token): array
    {
        return [
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'],
            'akses_kedaluwarsa_pada' => now()->addSeconds($token['expire_in']),
            'refresh_kedaluwarsa_pada' => now()->addDays(self::UMUR_REFRESH_HARI),
        ];
    }

    /** Nama toko hanya pelengkap tampilan; kalau gagal diambil, koneksi tetap disimpan. */
    private function namaToko(string $aksesToken, int $shopId): ?string
    {
        try {
            $nama = $this->klien->infoToko($aksesToken, $shopId)['shop_name'] ?? null;
        } catch (GalatShopee) {
            return null;
        }

        return is_string($nama) && $nama !== '' ? mb_substr($nama, 0, 120) : null;
    }
}
