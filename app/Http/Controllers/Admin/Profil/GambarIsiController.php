<?php

namespace App\Http\Controllers\Admin\Profil;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\GambarIsiRequest;
use App\Services\Profil\PenyimpanGambar;
use Illuminate\Http\JsonResponse;

// Dipanggil editor saat admin menyisipkan foto di tengah tulisan; editor lalu memasang alamatnya.
class GambarIsiController extends Controller
{
    public function __invoke(GambarIsiRequest $request, PenyimpanGambar $gambar): JsonResponse
    {
        $jalur = $gambar->simpan($request->file('gambar'), 'gambar');

        return response()->json(['url' => PenyimpanGambar::urlRelatif($jalur)], 201);
    }
}
