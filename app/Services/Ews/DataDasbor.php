<?php

namespace App\Services\Ews;

use App\Models\Ews\Pembacaan;
use App\Models\Ews\Pengaturan;
use App\Models\Ews\Wilayah;
use Illuminate\Support\Facades\Cache;

class DataDasbor
{
    private const KUNCI = 'ews:dasbor';

    private const DETIK_CACHE = 8;

    private const JUMLAH_LOG = 10;

    // Cukup untuk beberapa jam kiriman semua alat; log hanya perlu perubahan terbaru.
    private const BARIS_ACUAN_LOG = 400;

    /**
     * Banyak warga membuka dashboard bersamaan saat hujan: satu hasil dibagi ke semua pengunjung
     * selama beberapa detik, dan langsung dibuang saat ada data baru (lihat lupakan()).
     *
     * @return array{isi: string, etag: string}
     */
    public function terkemas(): array
    {
        return Cache::remember(self::KUNCI, self::DETIK_CACHE, function () {
            $isi = json_encode($this->bangun(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            return ['isi' => $isi, 'etag' => md5($isi)];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function ambil(): array
    {
        return json_decode($this->terkemas()['isi'], true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{status: string, warna: string, lokasi: ?string, waktu: ?string, ada_offline: bool}|null
     */
    public function ringkasan(): ?array
    {
        $data = $this->ambil();

        if ($data['utama']['status'] === null) {
            return null;
        }

        return [
            'status' => $data['utama']['status'],
            'warna' => $data['utama']['warna'],
            'lokasi' => $data['utama']['lokasi'],
            'waktu' => $data['utama']['waktu'],
            'ada_offline' => $data['offline'] !== [],
        ];
    }

    public static function lupakan(): void
    {
        Cache::forget(self::KUNCI);
    }

    /**
     * @return array<string, mixed>
     */
    private function bangun(): array
    {
        $ambang = Pengaturan::ambang();
        $batasOffline = (int) config('ews.alat_offline_menit');

        $wilayah = Wilayah::query()->with('pembacaanTerbaru')->orderBy('id')->get()
            ->map(fn (Wilayah $w) => $this->wilayah($w, $ambang, $batasOffline));

        return [
            'utama' => $this->utama($wilayah->all()),
            'wilayah' => $wilayah->all(),
            'offline' => $wilayah->where('offline', true)
                ->map(fn (array $w) => ['nama' => $w['nama'], 'waktu' => $w['waktu']])->values()->all(),
            'log' => $this->log(),
            'ambang' => $ambang,
        ];
    }

    /**
     * @param  array<string, array{waspada: float, validasi: float}>  $ambang
     * @return array<string, mixed>
     */
    private function wilayah(Wilayah $wilayah, array $ambang, int $batasOffline): array
    {
        $baca = $wilayah->pembacaanTerbaru;
        $status = PenilaianStatus::statusWilayah($baca?->status, $wilayah->status_awas);
        $level = $baca ? PenilaianStatus::levelSensor($baca->only(PenilaianStatus::SENSOR_DINILAI), $ambang) : [];

        $sensor = [];
        foreach (Sensor::DAFTAR as $kunci => $keterangan) {
            $nilai = $baca?->{$kunci};
            $lv = $level[$kunci] ?? null;

            $sensor[$kunci] = [
                'nama' => $keterangan['nama'],
                'ikon' => $keterangan['ikon'],
                'nilai' => $nilai,
                'teks' => Sensor::teks($kunci, $nilai),
                'level' => $lv,
                'kata' => match (true) {
                    $nilai === null => __('ews.level.kosong'),
                    $lv === null => __('ews.level.informasi'),
                    default => __("ews.level.{$lv}"),
                },
                'warna' => $lv === null ? PenilaianStatus::WARNA_KOSONG : ($lv === 0 ? 'hijau' : 'kuning'),
            ];
        }

        return [
            'id' => $wilayah->id,
            'nama' => $wilayah->nama,
            'poligon' => $wilayah->poligon,
            'status' => $status['status'],
            'warna' => $status['warna'],
            'pemicu' => $wilayah->status_awas ? $wilayah->awas_sumber : $baca?->pemicu,
            'waktu' => $baca?->created_at->format('d-m-Y H:i'),
            'offline' => PenilaianStatus::alatOffline($baca?->created_at, $batasOffline),
            'sensor' => $sensor,
        ];
    }

    /**
     * Status utama = kondisi terburuk dari semua wilayah.
     *
     * @param  array<int, array<string, mixed>>  $wilayah
     * @return array<string, mixed>
     */
    private function utama(array $wilayah): array
    {
        $warna = PenilaianStatus::warnaTerburuk(array_column($wilayah, 'warna'));
        $terburuk = collect($wilayah)->firstWhere('warna', $warna);
        $adaData = $warna !== PenilaianStatus::WARNA_KOSONG;

        return [
            'status' => $adaData ? $terburuk['status'] : null,
            'warna' => $warna,
            'lokasi' => $adaData ? $terburuk['nama'] : null,
            'pemicu' => $adaData ? ($terburuk['pemicu'] ?? 'Semua sensor normal') : null,
            'waktu' => $adaData ? $terburuk['waktu'] : null,
            'saran' => __("ews.saran.{$warna}"),
        ];
    }

    /**
     * Log berisi PERUBAHAN status, bukan tiap kiriman: alat mengirim tiap menit, sehingga daftar
     * kiriman mentah hanya akan menampilkan baris yang sama berulang.
     *
     * @return array<int, array<string, ?string>>
     */
    private function log(): array
    {
        $baris = Pembacaan::query()->with('wilayah:id,nama,deleted_at')
            ->orderByDesc('created_at')->orderByDesc('id')->limit(self::BARIS_ACUAN_LOG)
            ->get(['id', 'wilayah_id', 'status', 'warna', 'pemicu', 'validasi', 'created_at'])
            ->reverse();

        $terakhir = [];
        $kejadian = [];

        foreach ($baris as $b) {
            $sebelum = $terakhir[$b->wilayah_id] ?? null;
            $terakhir[$b->wilayah_id] = $b->status;

            // Bacaan pertama yang Aman bukan kejadian: tidak ada yang "kembali normal".
            $berubah = $sebelum === null ? $b->warna !== 'hijau' : $sebelum !== $b->status;

            if ($berubah || $b->validasi === Pembacaan::VALIDASI_DISETUJUI) {
                $kejadian[] = [
                    'waktu' => $b->created_at->format('d-m-Y H:i'),
                    'wilayah' => $b->wilayah->nama,
                    'status' => $b->status,
                    'warna' => $b->warna,
                    'keterangan' => $b->pemicu ?? 'Kembali normal',
                ];
            }
        }

        return array_slice(array_reverse($kejadian), 0, self::JUMLAH_LOG);
    }
}
