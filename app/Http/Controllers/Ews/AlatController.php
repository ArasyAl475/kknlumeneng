<?php

namespace App\Http\Controllers\Ews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ews\SimpanPembacaanRequest;
use App\Models\Ews\Pembacaan;
use App\Models\Ews\Wilayah;
use App\Services\Ews\PenerimaPembacaan;
use Illuminate\Http\JsonResponse;

class AlatController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json([
            'pesan' => 'OK',
            'waktu_server' => now()->toIso8601String(),
            'wilayah' => Wilayah::query()->orderBy('id')->get(['id', 'nama']),
        ]);
    }

    public function simpan(SimpanPembacaanRequest $request, PenerimaPembacaan $penerima): JsonResponse
    {
        $pembacaan = $penerima->terima($request->wilayah(), $request->bacaan(), $request->boolean('kabel_putus'));

        return response()->json([
            'pesan' => 'Data tersimpan',
            'menunggu_validasi' => $pembacaan->validasi === Pembacaan::VALIDASI_MENUNGGU,
            // Selalu null: pesan baru dikirim setelah respons ini, agar alat tidak menunggu Telegram.
            'telegram_terkirim' => null,
            'waktu_server' => now()->toIso8601String(),
            'data' => ['id' => $pembacaan->id, 'status' => $pembacaan->status, 'warna' => $pembacaan->warna],
        ], 201);
    }
}
