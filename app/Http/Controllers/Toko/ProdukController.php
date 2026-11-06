<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use App\Models\Toko\Marketplace;
use App\Models\Toko\Pengaturan;
use App\Models\Toko\Produk;
use App\Services\Toko\KatalogPublik;
use App\Services\Toko\TautanBeli;
use Illuminate\View\View;

class ProdukController extends Controller
{
    public function __invoke(string $marketplace, Produk $produk): View
    {
        $semuaMarketplace = Marketplace::semuaTerindeks();
        $data = $semuaMarketplace[$marketplace];
        abort_unless($produk->tampil && $data->aktif, 404);

        $produk->load(['foto', 'varian', 'tautan']);

        // Tautan lama di WhatsApp tetap terbuka walau produk belum ada di marketplace ini;
        // pengunjung diarahkan ke marketplace lain yang menyediakannya.
        $lainnya = $semuaMarketplace->except($marketplace)
            ->map(fn (Marketplace $m) => TautanBeli::untuk($produk, $m))
            ->filter();

        return view('toko.produk', [
            'kunci' => $marketplace,
            'marketplace' => $data,
            'produk' => $produk,
            'beli' => TautanBeli::untuk($produk, $data),
            'lainnya' => $lainnya,
            'pengaturan' => Pengaturan::semua(),
            'produkLain' => KatalogPublik::produkDi($data)->reject(fn (Produk $p) => $p->is($produk))->take(4),
        ]);
    }
}
