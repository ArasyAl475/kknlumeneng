<?php

namespace App\Http\Controllers\Admin\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\MarketplaceRequest;
use App\Models\Toko\Aktivitas;
use App\Models\Toko\Marketplace;
use App\Models\Toko\Produk;
use App\Services\Toko\DaftarMarketplace;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function show(string $marketplace): View
    {
        return view('admin.toko.marketplace', [
            'kunci' => $marketplace,
            'marketplace' => Marketplace::untuk($marketplace),
            'produk' => Produk::query()->with(['foto', 'tautan', 'varian'])->orderBy('urutan')->orderBy('id')->get(),
        ]);
    }

    public function update(MarketplaceRequest $request, string $marketplace): RedirectResponse
    {
        Marketplace::query()->updateOrCreate(['kunci' => $marketplace], $request->dataMarketplace());
        $nama = DaftarMarketplace::nama($marketplace);
        Aktivitas::catat($request->user(), 'ubah_marketplace', "Mengubah alamat dan status {$nama}.");

        return to_route('admin.toko.marketplace.show', $marketplace)->with('sukses', "Pengaturan {$nama} disimpan.");
    }
}
