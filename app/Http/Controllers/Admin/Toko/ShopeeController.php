<?php

namespace App\Http\Controllers\Admin\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\KembaliShopeeRequest;
use App\Models\Toko\Aktivitas;
use App\Services\Toko\Shopee\GalatShopee;
use App\Services\Toko\Shopee\KlienShopee;
use App\Services\Toko\Shopee\KoneksiShopee;
use App\Services\Toko\Shopee\SinkronProduk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShopeeController extends Controller
{
    private const KUNCI_SESI = 'toko.shopee.izin';

    private const UMUR_IZIN_MENIT = 15;

    public function hubungkan(Request $request, KlienShopee $klien): RedirectResponse
    {
        if (! $klien->siap()) {
            return $this->keHalaman()->with('galat', GalatShopee::belumDiatur()->getMessage());
        }

        // Nonce sekali pakai di alamat kembali: memastikan yang kembali dari Shopee adalah izin yang
        // dimulai admin ini sendiri, bukan tautan kiriman orang lain yang menyambungkan toko lain.
        $nonce = Str::random(40);
        $request->session()->put(self::KUNCI_SESI, ['nonce' => $nonce, 'sampai' => now()->addMinutes(self::UMUR_IZIN_MENIT)->timestamp]);

        return redirect()->away($klien->urlIzin(route('admin.toko.shopee.kembali', $nonce)));
    }

    public function kembali(KembaliShopeeRequest $request, string $nonce, KoneksiShopee $koneksi): RedirectResponse
    {
        $izin = $request->session()->pull(self::KUNCI_SESI);

        if (! is_array($izin) || ($izin['sampai'] ?? 0) < now()->timestamp || ! hash_equals((string) ($izin['nonce'] ?? ''), $nonce)) {
            return $this->keHalaman()->with('galat', 'Izin Shopee kedaluwarsa atau tidak sah. Tekan "Hubungkan dengan Shopee" lagi.');
        }

        try {
            $token = $koneksi->hubungkan($request->kode(), $request->shopId(), $request->user());
        } catch (GalatShopee $galat) {
            report($galat);

            return $this->keHalaman()->with('galat', 'Shopee menolak koneksi: '.$galat->getMessage());
        }

        return $this->keHalaman()->with('sukses', $token->nama_toko
            ? "Toko Shopee {$token->nama_toko} berhasil dihubungkan."
            : 'Toko Shopee berhasil dihubungkan.');
    }

    public function sinkron(Request $request, SinkronProduk $sinkron): RedirectResponse
    {
        try {
            $hasil = $sinkron->jalankan();
        } catch (GalatShopee $galat) {
            // Sengaja bukan report(): jejak galat mencetak argumen fungsi, termasuk token akses.
            Log::warning('Sinkron produk Shopee gagal: '.$galat->getMessage());

            return $this->keHalaman()->with('galat', 'Sinkron gagal: '.$galat->getMessage());
        }

        Aktivitas::catat($request->user(), 'sinkron_shopee', "Sinkron produk Shopee: {$hasil['dibuat']} dibuat, {$hasil['diperbarui']} diperbarui, {$hasil['dilewati']} dilewati, {$hasil['gagal']} gagal.");

        return $this->keHalaman()->with('sukses', "Sinkron selesai: {$hasil['dibuat']} produk baru, {$hasil['diperbarui']} diperbarui, {$hasil['dilewati']} dilewati, {$hasil['gagal']} gagal.");
    }

    public function putuskan(Request $request, KoneksiShopee $koneksi): RedirectResponse
    {
        $koneksi->putuskan($request->user());

        return $this->keHalaman()->with('sukses', 'Koneksi Shopee diputus. Produk yang sudah ada tetap tersimpan.');
    }

    private function keHalaman(): RedirectResponse
    {
        return to_route('admin.toko.marketplace.show', 'shopee');
    }
}
