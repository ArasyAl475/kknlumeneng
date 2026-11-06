<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use App\Models\Toko\Klik;
use App\Models\Toko\Marketplace;
use App\Models\Toko\Produk;
use App\Services\Toko\TautanBeli;
use Illuminate\Http\RedirectResponse;

class BeliController extends Controller
{
    public function __invoke(string $marketplace, Produk $produk): RedirectResponse
    {
        abort_unless($produk->tampil, 404);

        $tujuan = TautanBeli::untuk($produk->load('tautan'), Marketplace::untuk($marketplace));
        abort_if($tujuan === null, 404);

        Klik::query()->create(['produk_id' => $produk->id, 'marketplace' => $marketplace]);

        return redirect()->away($tujuan['url']);
    }
}
