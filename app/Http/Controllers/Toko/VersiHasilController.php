<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use App\Services\Toko\VersiHasil;
use Illuminate\Http\JsonResponse;

class VersiHasilController extends Controller
{
    public function __invoke(VersiHasil $versi): JsonResponse
    {
        // no-store: jawaban lama dari cache browser akan membuat halaman tidak pernah tahu ada data baru.
        return response()->json(['versi' => $versi->sekarang()])->header('Cache-Control', 'no-store');
    }
}
