<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use App\Models\Toko\Marketplace;
use App\Services\Toko\KatalogPublik;
use Illuminate\View\View;

class TokoController extends Controller
{
    public function index(): View
    {
        $produk = KatalogPublik::semuaTampil();
        $marketplace = Marketplace::semuaTerindeks()->filter(fn (Marketplace $m) => $m->aktif);

        return view('toko.index', [
            'marketplace' => $marketplace,
            'jumlah' => $marketplace->map(fn (Marketplace $m) => KatalogPublik::produkDi($m, $produk)->count()),
        ]);
    }

    public function marketplace(string $marketplace): View
    {
        $data = Marketplace::untuk($marketplace);
        abort_unless($data->aktif, 404);

        return view('toko.marketplace', [
            'kunci' => $marketplace,
            'marketplace' => $data,
            'produk' => KatalogPublik::produkDi($data),
        ]);
    }
}
