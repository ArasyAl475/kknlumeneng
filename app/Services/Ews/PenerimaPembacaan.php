<?php

namespace App\Services\Ews;

use App\Models\Ews\Pembacaan;
use App\Models\Ews\Pengaturan;
use App\Models\Ews\Wilayah;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PenerimaPembacaan
{
    public function __construct(private PemberitahuTelegram $telegram) {}

    /**
     * Pintu tunggal data sensor masuk, dipakai API alat maupun simulator.
     *
     * @param  array<string, float|null>  $bacaan
     */
    public function terima(Wilayah $wilayah, array $bacaan, bool $kabelPutus = false): Pembacaan
    {
        $hasil = PenilaianStatus::nilai($bacaan, Pengaturan::ambang(), $kabelPutus);

        // Kunci baris wilayah supaya dua kiriman bersamaan tidak membuat dua laporan
        // "menunggu" atau dua pesan Awas untuk wilayah yang sama.
        [$pembacaan, $mulaiAwas] = DB::transaction(function () use ($wilayah, $bacaan, $hasil, $kabelPutus) {
            $wilayah = Wilayah::query()->lockForUpdate()->findOrFail($wilayah->id);

            $menunggu = $hasil['perlu_validasi'] && ! $this->validasiSudahDiminta($wilayah);

            $pembacaan = Pembacaan::query()->create([
                'wilayah_id' => $wilayah->id,
                'kelembapan_tanah' => $bacaan['kelembapan_tanah'] ?? null,
                'curah_hujan' => $bacaan['curah_hujan'] ?? null,
                'kemiringan' => $bacaan['kemiringan'] ?? null,
                'kelembapan_udara' => $bacaan['kelembapan_udara'] ?? null,
                'kabel_putus' => $kabelPutus,
                'status' => $hasil['status'],
                'warna' => $hasil['warna'],
                'pemicu' => $hasil['pemicu'],
                'validasi' => $menunggu ? Pembacaan::VALIDASI_MENUNGGU : null,
            ]);

            $mulaiAwas = $kabelPutus && ! $wilayah->status_awas;
            if ($mulaiAwas) {
                $wilayah->update([
                    'status_awas' => true,
                    'awas_sumber' => 'Kabel EWS putus (otomatis)',
                    'awas_sejak' => now(),
                ]);
            }

            return [$pembacaan, $mulaiAwas];
        });

        Cache::forget($this->kunciOffline($wilayah));
        $pembacaan->setRelation('wilayah', $wilayah);

        if ($mulaiAwas) {
            $this->telegram->awasKabelPutus($pembacaan);
        } elseif ($pembacaan->validasi === Pembacaan::VALIDASI_MENUNGGU) {
            $this->telegram->perluValidasi($pembacaan);
        }

        return $pembacaan;
    }

    public function kunciOffline(Wilayah $wilayah): string
    {
        return "ews:offline-diberitahu:{$wilayah->id}";
    }

    // Alat mengirim terus-menerus; admin hanya boleh diminta memvalidasi sekali.
    private function validasiSudahDiminta(Wilayah $wilayah): bool
    {
        if ($wilayah->status_awas) {
            return true;
        }

        return Pembacaan::query()
            ->where('wilayah_id', $wilayah->id)
            ->where(function ($query) {
                $query->where('validasi', Pembacaan::VALIDASI_MENUNGGU)
                    ->orWhere(fn ($q) => $q
                        ->whereIn('validasi', [Pembacaan::VALIDASI_DISETUJUI, Pembacaan::VALIDASI_DITOLAK])
                        ->where('divalidasi_pada', '>=', now()->subMinutes(max(0, (int) config('ews.validasi_jeda_menit')))));
            })
            ->exists();
    }
}
