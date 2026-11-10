<?php

namespace App\Http\Controllers\Admin\Toko;

use App\Http\Controllers\Controller;
use App\Services\Toko\GoogleSheets;
use App\Services\Toko\SinkronSheetHasil;
use Illuminate\Http\RedirectResponse;

class BukaSheetController extends Controller
{
    /**
     * Lewat rute (bukan tautan langsung di halaman) supaya nomor tab baru dicari saat tombol ditekan,
     * sehingga membuka daftar panen tidak pernah menunggu Google.
     */
    public function __invoke(string $jenis, GoogleSheets $sheets): RedirectResponse
    {
        abort_unless($sheets->adaAlamat(), 404);

        return redirect()->away($sheets->alamatWeb(SinkronSheetHasil::namaTab($jenis)));
    }
}
