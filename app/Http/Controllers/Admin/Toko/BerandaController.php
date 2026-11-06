<?php

namespace App\Http\Controllers\Admin\Toko;

use App\Http\Controllers\Controller;
use App\Models\Toko\Klik;
use App\Models\Toko\Marketplace;
use App\Models\Toko\Produk;
use App\Models\Toko\TautanProduk;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.toko.beranda', [
            'marketplace' => Marketplace::semuaTerindeks(),
            'jumlahProduk' => Produk::query()->count(),
            // whereHas ikut menyaring produk yang terhapus lunak.
            'jumlahTautan' => TautanProduk::query()->whereHas('produk')
                ->selectRaw('marketplace, count(*) as jumlah')->groupBy('marketplace')
                ->pluck('jumlah', 'marketplace')->map(fn ($n) => (int) $n),
            'klik' => Klik::jumlahSejak(now()->subDays(30)),
        ]);
    }
}
