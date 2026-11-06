<?php

namespace App\Services\Toko;

use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use App\Models\Toko\PenjualanJamur;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RekapHasil
{
    // Rincian harian dibatasi agar halaman tetap ringan di sinyal lemah.
    public const BATAS_HARIAN = 31;

    // Tabel catatan timbang di halaman dibatasi agar tetap ringan; Excel memuat semuanya.
    public const BATAS_TIMBANG = 50;

    // Kunci 1 dan 0 sama dengan nilai kolom dengan_cangkang_kopi.
    public const MEDIA = [1 => 'Dengan cangkang kopi', 0 => 'Tanpa cangkang kopi'];

    /**
     * @param  array{dari?: ?string, sampai?: ?string, jenis?: ?string, tempat?: ?string}  $filter
     */
    public function kopi(array $filter = []): array
    {
        $baris = $this->kopiTersaring($filter)
            ->orderByDesc('tanggal_panen')
            ->orderByDesc('id')
            ->get(['tanggal_panen', 'jenis_kopi', 'tempat_panen', 'nama_petani', 'jumlah_kg', 'limbah_kg']);

        $perHari = $this->kelompokkan($baris, fn (PanenKopi $p) => FormatHasil::hari($p->tanggal_panen));
        $panen = round($baris->sum('jumlah_kg'), 2);
        $limbah = round($baris->sum('limbah_kg'), 2);

        return [
            'panen' => $panen,
            'limbah' => $limbah,
            'persen_limbah' => $this->persen($limbah, $panen),
            'per_jenis' => $this->kelompokkan($baris->sortBy('jenis_kopi'), fn (PanenKopi $p) => $p->jenis_kopi),
            'per_tempat' => $this->kelompokkan($baris->sortBy('tempat_panen'), fn (PanenKopi $p) => $p->tempat_panen),
            'per_tahun' => $this->kelompokkan($baris, fn (PanenKopi $p) => $p->tanggal_panen->format('Y')),
            'per_bulan' => $this->kelompokkan($baris, fn (PanenKopi $p) => $p->tanggal_panen->translatedFormat('F Y')),
            'per_hari' => array_slice($perHari, 0, self::BATAS_HARIAN),
            'hari_terpotong' => count($perHari) > self::BATAS_HARIAN,
            // Catatan timbang terbaru. Berat bersih = berat kotor (jumlah panen) dikurangi limbah.
            'timbang' => $baris->take(self::BATAS_TIMBANG)->map(fn (PanenKopi $p) => [
                'tanggal' => $p->tanggal_panen,
                'petani' => $p->nama_petani,
                'blok' => $p->tempat_panen,
                'kotor' => (float) $p->jumlah_kg,
                'bersih' => max(round((float) $p->jumlah_kg - (float) $p->limbah_kg, 2), 0.0),
            ])->all(),
            'timbang_jumlah' => $baris->count(),
            'bersih' => max(round($panen - $limbah, 2), 0.0),
        ];
    }

    /**
     * Catatan mentah, urut dari yang paling lama, untuk berkas unduhan. Memakai filter yang sama
     * dengan kopi() supaya angka di Excel selalu cocok dengan angka di halaman.
     *
     * @param  array{dari?: ?string, sampai?: ?string, jenis?: ?string, tempat?: ?string}  $filter
     * @return Collection<int, PanenKopi>
     */
    public function daftarKopi(array $filter = []): Collection
    {
        return $this->kopiTersaring($filter)->orderBy('tanggal_panen')->orderBy('id')->get();
    }

    /**
     * @param  array{dari?: ?string, sampai?: ?string}  $filter
     * @return Collection<int, PanenJamur>
     */
    public function daftarPanenJamur(array $filter = []): Collection
    {
        return $this->dalamRentang(PanenJamur::query(), 'tanggal_panen', $filter)->orderBy('tanggal_panen')->orderBy('id')->get();
    }

    /**
     * @param  array{dari?: ?string, sampai?: ?string}  $filter
     * @return Collection<int, PenjualanJamur>
     */
    public function daftarPenjualanJamur(array $filter = []): Collection
    {
        return $this->dalamRentang(PenjualanJamur::query(), 'tanggal_penjualan', $filter)->orderBy('tanggal_penjualan')->orderBy('id')->get();
    }

    /**
     * @param  array{dari?: ?string, sampai?: ?string}  $filter
     * @return array{total: float, cangkang: float, persen_cangkang: float}
     */
    public function jamur(array $filter = []): array
    {
        $query = $this->dalamRentang(PanenJamur::query(), 'tanggal_panen', $filter);
        $total = round((float) (clone $query)->sum('jumlah_kg'), 2);
        $cangkang = round((float) (clone $query)->where('dengan_cangkang_kopi', true)->sum('jumlah_kg'), 2);

        return [
            'total' => $total,
            'cangkang' => $cangkang,
            'persen_cangkang' => $this->persen($cangkang, $total),
        ];
    }

    /**
     * Bahan perbandingan untuk warga. Sengaja tanpa Rupiah: harga hanya untuk admin
     * (lihat keuanganJamur), supaya tidak ikut tampil di halaman publik.
     *
     * @param  array{dari?: ?string, sampai?: ?string}  $filter
     * @return array<int, array{label: string, baglog: int, panen: float, kg_per_baglog: ?float, terjual_kg: float}>
     */
    public function perbandinganJamur(array $filter = []): array
    {
        [$panen, $terjual] = $this->agregatMedia($filter);

        return collect(self::MEDIA)->map(function (string $label, int $kunci) use ($panen, $terjual) {
            $p = $panen->get($kunci);
            $baglog = (int) ($p->baglog ?? 0);

            return [
                'label' => $label,
                'baglog' => $baglog,
                'panen' => round((float) ($p->kg ?? 0), 2),
                // Panen lama tanpa data baglog tidak ikut dibagi, agar hasil per baglog tidak tampak terlalu kecil.
                'kg_per_baglog' => $baglog > 0 ? round((float) $p->kg_berbaglog / $baglog, 2) : null,
                'terjual_kg' => round((float) ($terjual->get($kunci)->kg ?? 0), 2),
            ];
        })->values()->all();
    }

    /**
     * Khusus admin: harga jual rata-rata per kg dan biaya membuat satu baglog, per media tanam.
     *
     * @param  array{dari?: ?string, sampai?: ?string}  $filter
     * @return array<int, array{label: string, harga_per_kg: ?int, biaya_per_baglog: ?int}>
     */
    public function keuanganJamur(array $filter = []): array
    {
        [$panen, $terjual] = $this->agregatMedia($filter);

        return collect(self::MEDIA)->map(function (string $label, int $kunci) use ($panen, $terjual) {
            $p = $panen->get($kunci);
            $t = $terjual->get($kunci);
            $baglogBerbiaya = (int) ($p->baglog_berbiaya ?? 0);

            return [
                'label' => $label,
                'harga_per_kg' => $t && $t->kg > 0 ? (int) round($t->rupiah / $t->kg) : null,
                'biaya_per_baglog' => $baglogBerbiaya > 0 ? (int) round($p->biaya / $baglogBerbiaya) : null,
            ];
        })->values()->all();
    }

    /**
     * @param  array{dari?: ?string, sampai?: ?string}  $filter
     * @return array{kg: float, rupiah: int}
     */
    public function penjualan(array $filter = []): array
    {
        $query = $this->dalamRentang(PenjualanJamur::query(), 'tanggal_penjualan', $filter);

        return [
            'kg' => round((float) (clone $query)->sum('jumlah_kg'), 2),
            'rupiah' => (int) (clone $query)->sum('total_rupiah'),
        ];
    }

    /**
     * Pilihan yang sudah pernah dicatat, untuk filter publik dan saran isian admin.
     *
     * @return array{jenis: Collection<int, string>, tempat: Collection<int, string>, petani: Collection<int, string>}
     */
    public function pilihanKopi(): array
    {
        return [
            'jenis' => PanenKopi::query()->distinct()->orderBy('jenis_kopi')->pluck('jenis_kopi'),
            'tempat' => PanenKopi::query()->distinct()->orderBy('tempat_panen')->pluck('tempat_panen'),
            'petani' => PanenKopi::query()->whereNotNull('nama_petani')->distinct()->orderBy('nama_petani')->pluck('nama_petani'),
        ];
    }

    /**
     * Pintasan waktu dan kalimat penjelas, agar warga tidak perlu mengisi tanggal untuk
     * pertanyaan yang paling sering: "tahun ini berapa?" dan "bulan ini berapa?".
     *
     * @param  array{dari?: ?string, sampai?: ?string, jenis?: ?string, tempat?: ?string}  $filter
     * @return array{pilihan: array<int, array{label: string, kueri: array<string, string>, aktif: bool}>, keterangan: string, lanjutan: bool}
     */
    public function periode(array $filter = []): array
    {
        $dari = $filter['dari'] ?? null;
        $sampai = $filter['sampai'] ?? null;
        $lainnya = array_filter(['jenis' => $filter['jenis'] ?? null, 'tempat' => $filter['tempat'] ?? null]);
        $kini = now();

        $pilihan = collect([
            ['Semua waktu', null, null],
            ['Tahun ini', $kini->copy()->startOfYear()->format('Y-m-d'), $kini->copy()->endOfYear()->format('Y-m-d')],
            ['Bulan ini', $kini->copy()->startOfMonth()->format('Y-m-d'), $kini->copy()->endOfMonth()->format('Y-m-d')],
        ])->map(fn (array $p) => [
            'label' => $p[0],
            'kueri' => array_filter(['dari' => $p[1], 'sampai' => $p[2]]) + $lainnya,
            'aktif' => $dari === $p[1] && $sampai === $p[2],
        ]);

        $aktif = $pilihan->firstWhere('aktif', true);
        $waktu = $aktif['label'] ?? match (true) {
            $dari && $sampai => FormatHasil::tanggal($dari).' sampai '.FormatHasil::tanggal($sampai),
            (bool) $dari => 'Sejak '.FormatHasil::tanggal($dari),
            default => 'Sampai '.FormatHasil::tanggal($sampai),
        };

        return [
            'pilihan' => $pilihan->all(),
            'keterangan' => implode(' · ', [$waktu, ...array_values($lainnya)]),
            'lanjutan' => ! $aktif || $lainnya !== [],
        ];
    }

    /**
     * @return array{0: Collection, 1: Collection} panen dan penjualan jamur, masing-masing berkunci 1 (dengan cangkang) / 0 (tanpa)
     */
    private function agregatMedia(array $filter): array
    {
        $panen = $this->dalamRentang(PanenJamur::query(), 'tanggal_panen', $filter)
            ->selectRaw('dengan_cangkang_kopi as media, SUM(jumlah_kg) as kg, SUM(jumlah_baglog) as baglog,'
                .' SUM(CASE WHEN jumlah_baglog IS NOT NULL THEN jumlah_kg ELSE 0 END) as kg_berbaglog,'
                .' SUM(biaya_baglog_rupiah) as biaya,'
                .' SUM(CASE WHEN biaya_baglog_rupiah IS NOT NULL THEN jumlah_baglog ELSE 0 END) as baglog_berbiaya')
            ->groupBy('dengan_cangkang_kopi')
            ->get()
            ->keyBy(fn ($baris) => (int) $baris->media);

        // Penjualan lama belum mencatat media tanam, jadi tidak bisa dimasukkan ke salah satu sisi.
        $terjual = $this->dalamRentang(PenjualanJamur::query(), 'tanggal_penjualan', $filter)
            ->whereNotNull('dengan_cangkang_kopi')
            ->selectRaw('dengan_cangkang_kopi as media, SUM(jumlah_kg) as kg, SUM(total_rupiah) as rupiah')
            ->groupBy('dengan_cangkang_kopi')
            ->get()
            ->keyBy(fn ($baris) => (int) $baris->media);

        return [$panen, $terjual];
    }

    private function kopiTersaring(array $filter): Builder
    {
        return $this->dalamRentang(PanenKopi::query(), 'tanggal_panen', $filter)
            ->when($filter['jenis'] ?? null, fn (Builder $q, string $jenis) => $q->where('jenis_kopi', $jenis))
            ->when($filter['tempat'] ?? null, fn (Builder $q, string $tempat) => $q->where('tempat_panen', $tempat));
    }

    private function persen(float $bagian, float $keseluruhan): float
    {
        return $keseluruhan > 0 ? round($bagian / $keseluruhan * 100, 1) : 0.0;
    }

    // Batas memakai jam penuh, bukan tanggal saja: SQLite menyimpan kolom tanggal sebagai teks
    // "Y-m-d 00:00:00", sehingga perbandingan dengan "Y-m-d" akan melewatkan hari terakhir.
    private function dalamRentang(Builder $query, string $kolom, array $filter): Builder
    {
        return $query
            ->when($filter['dari'] ?? null, fn (Builder $q, string $dari) => $q->where($kolom, '>=', Carbon::parse($dari)->startOfDay()))
            ->when($filter['sampai'] ?? null, fn (Builder $q, string $sampai) => $q->where($kolom, '<=', Carbon::parse($sampai)->endOfDay()));
    }

    /**
     * @return array<int, array{label: string, panen: float, limbah: float, lebar: float}>
     */
    private function kelompokkan(Collection $baris, callable $label): array
    {
        $kelompok = $baris->groupBy($label)
            ->map(fn (Collection $grup, string $nama) => [
                'label' => $nama,
                'panen' => round($grup->sum('jumlah_kg'), 2),
                'limbah' => round($grup->sum('limbah_kg'), 2),
            ])
            ->values();
        $terbesar = (float) $kelompok->max('panen');

        return $kelompok->map(fn (array $k) => $k + ['lebar' => $this->persen($k['panen'], $terbesar)])->all();
    }
}
