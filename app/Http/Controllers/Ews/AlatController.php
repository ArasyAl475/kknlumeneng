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
            // Kunci "id" tetap sesuai kontrak alat, isinya ID alat yang dipakai di wilayah_id.
            'wilayah' => Wilayah::query()->orderBy('id_alat')->get(['id_alat', 'nama'])
                ->map(fn (Wilayah $w) => ['id' => $w->id_alat, 'nama' => $w->nama])->values(),
        ]);
    }

    public function simpan(SimpanPembacaanRequest $request, PenerimaPembacaan $penerima): JsonResponse
    {
        $pembacaan = $penerima->terima($request->wilayah(), $request->bacaan(), $request->boolean('kabel_putus'));

        return response()->json([
            'pesan' => 'Data tersimpan',
            'menunggu_validasi' => $pembacaan->validasi === Pembacaan::VALIDASI_MENUNGGU,
            // Selalu null. Dipertahankan karena firmware alat sudah mengikuti kontrak API ini.
            'telegram_terkirim' => null,
            'waktu_server' => now()->toIso8601String(),
            'data' => ['id' => $pembacaan->id, 'status' => $pembacaan->status, 'warna' => $pembacaan->warna],
        ], 201);
    }
}
