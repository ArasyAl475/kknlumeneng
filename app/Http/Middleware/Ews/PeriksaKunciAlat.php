<?php

namespace App\Http\Middleware\Ews;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PeriksaKunciAlat
{
    public function handle(Request $request, Closure $next): Response
    {
        $kunci = (string) config('ews.api_key');

        // Kunci kosong di .env berarti menolak semua, bukan membuka semua.
        if ($kunci === '' || ! hash_equals($kunci, (string) $request->header('X-API-KEY'))) {
            return response()->json(['pesan' => 'API key salah atau belum diatur di .env'], 401);
        }

        return $next($request);
    }
}
