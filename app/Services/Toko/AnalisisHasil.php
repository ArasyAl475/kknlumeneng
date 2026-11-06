<?php

namespace App\Services\Toko;

use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use App\Models\Toko\PenjualanJamur;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Bahan tampilan tambahan untuk halaman hasil: deret waktu grafik, perubahan dibanding periode
 * sebelumnya, chip filter, dan kesimpulan baglog. Hanya membaca; angka total tetap dari RekapHasil
 * supaya ringkasan, grafik, dan rincian selalu memakai filter yang sama.
 */
class AnalisisHasil
{
    // Grafik harian dan bulanan dibatasi agar JSON dan kanvas tetap ringan di HP.
    public const BATAS_HARI = 90;

    public const BATAS_BULAN = 36;

    // Disingkat sendiri (bukan translatedFormat) agar sama dengan Intl id-ID di peramban: Agu, bukan Agt.
    private const BULAN_PENDEK = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public function __construct(private RekapHasil $rekap) {}

    /**
     * Deret waktu per hari, bulan, dan tahun untuk rentang yang sedang dipilih.
     * Hari tanpa catatan bernilai 0 supaya sumbu waktu jujur (panen kopi memang musiman).
     *
     * @param  array{dari?: ?string, sampai?: ?string, jenis?: ?string, tempat?: ?string}  $filter
     * @return array{ada: bool, skala: array<string, array<string, mixed>>}
     */
    public function deret(array $filter = []): array
    {
        $kopi = $this->agregatHarian(
            PanenKopi::query()
                ->when($filter['jenis'] ?? null, fn (Builder $q, string $jenis) => $q->where('jenis_kopi', $jenis))
                ->when($filter['tempat'] ?? null, fn (Builder $q, string $tempat) => $q->where('tempat_panen', $tempat)),
            'tanggal_panen', ['jumlah_kg'], $filter,
        );
        $jamur = $this->agregatHarian(PanenJamur::query(), 'tanggal_panen', ['jumlah_kg'], $filter);
        $penjualan = $this->agregatHarian(PenjualanJamur::query(), 'tanggal_penjualan', ['jumlah_kg', 'total_rupiah'], $filter);

        $harian = [
            'kopi' => $kopi['jumlah_kg'],
            'jamur' => $jamur['jumlah_kg'],
            'terjual' => $penjualan['jumlah_kg'],
            'pendapatan' => $penjualan['total_rupiah'],
        ];

        $tanggal = collect($harian)->flatMap(fn (array $seri) => array_keys($seri));
        if ($tanggal->isEmpty()) {
            return ['ada' => false, 'skala' => []];
        }

        $awal = ($filter['dari'] ?? null) ? Carbon::parse($filter['dari'])->startOfDay() : Carbon::parse($tanggal->min())->startOfDay();
        // Tanggal yang belum terjadi bukan "0 panen", jadi sumbu berhenti di hari ini.
        $akhir = ($filter['sampai'] ?? null)
            ? Carbon::parse($filter['sampai'])->startOfDay()->min(now()->startOfDay())
            : Carbon::parse($tanggal->max())->startOfDay();

        if ($akhir->lt($awal)) {
            return ['ada' => false, 'skala' => []];
        }

        return [
            'ada' => true,
            'skala' => [
                'hari' => $this->skalaHari($harian, $awal, $akhir),
                'bulan' => $this->skalaBulan($harian, $awal, $akhir),
                'tahun' => $this->skalaTahun($harian, $awal, $akhir),
            ],
        ];
    }

    /**
     * Perubahan tiap angka ringkasan dibanding periode sebelumnya. Hanya ada jika rentang
     * tanggal lengkap (Tahun ini, Bulan ini, atau tanggal pilihan sendiri). Periode yang belum
     * selesai dibandingkan dengan jumlah hari yang sama, supaya "bulan ini" tidak tampak anjlok.
     *
     * @param  array{dari?: ?string, sampai?: ?string, jenis?: ?string, tempat?: ?string}  $filter
     * @return ?array<string, mixed>
     */
    public function perubahan(array $filter, array $kopi, array $jamur, array $penjualan): ?array
    {
        if (empty($filter['dari']) || empty($filter['sampai'])) {
            return null;
        }

        $awal = Carbon::parse($filter['dari'])->startOfDay();
        $akhir = Carbon::parse($filter['sampai'])->startOfDay();
        $hariIni = now()->startOfDay();
        if ($awal->gt($hariIni)) {
            return null;
        }

        $hariBerjalan = $this->selisihHari($awal, $akhir->min($hariIni)) + 1;
        $sebulanPenuh = $awal->isSameDay($awal->copy()->startOfMonth()) && $akhir->isSameDay($awal->copy()->endOfMonth());
        $setahunPenuh = $awal->isSameDay($awal->copy()->startOfYear()) && $akhir->isSameDay($awal->copy()->endOfYear());

        if ($sebulanPenuh) {
            $awalLalu = $awal->copy()->subMonthNoOverflow();
            $akhirLaluPenuh = $awalLalu->copy()->endOfMonth()->startOfDay();
            $label = 'bulan lalu';
        } elseif ($setahunPenuh) {
            $awalLalu = $awal->copy()->subYear();
            $akhirLaluPenuh = $awalLalu->copy()->endOfYear()->startOfDay();
            $label = 'tahun lalu';
        } else {
            $akhirLaluPenuh = $awal->copy()->subDay();
            $awalLalu = $akhirLaluPenuh->copy()->subDays($this->selisihHari($awal, $akhir));
            $label = 'periode sebelumnya';
        }
        $akhirLalu = $akhirLaluPenuh->min($awalLalu->copy()->addDays($hariBerjalan - 1));

        $filterLalu = [
            'dari' => $awalLalu->format('Y-m-d'),
            'sampai' => $akhirLalu->format('Y-m-d'),
            'jenis' => $filter['jenis'] ?? null,
            'tempat' => $filter['tempat'] ?? null,
        ];
        $kopiLalu = $this->rekap->kopi($filterLalu);
        $penjualanLalu = $this->rekap->penjualan($filterLalu);

        $perubahan = [
            'kopi' => $this->selisih($kopi['panen'], $kopiLalu['panen']),
            'limbah' => $this->selisih($kopi['limbah'], $kopiLalu['limbah']),
            'jamur' => $this->selisih($jamur['total'], $this->rekap->jamur($filterLalu)['total']),
            'terjual' => $this->selisih($penjualan['kg'], $penjualanLalu['kg']),
            'pendapatan' => $this->selisih($penjualan['rupiah'], $penjualanLalu['rupiah']),
        ];

        if (array_filter($perubahan) === []) {
            return null;
        }

        return $perubahan + [
            'label' => $label,
            'keterangan' => 'Dibandingkan dengan '.FormatHasil::tanggal($filterLalu['dari']).' sampai '.FormatHasil::tanggal($filterLalu['sampai']).'.',
        ];
    }

    /**
     * Chip untuk pilihan yang sedang aktif. Pintasan waktu (Tahun ini, Bulan ini) tidak diberi
     * chip karena sudah terlihat pada pilihan waktu di atasnya.
     *
     * @param  array{dari?: ?string, sampai?: ?string, jenis?: ?string, tempat?: ?string}  $filter
     * @param  array{pilihan: array<int, array{aktif: bool}>}  $periode
     * @return array{chip: array<int, array{label: string, kueri: array<string, string>}>, ada: bool, jumlah: int}
     */
    public function penyaringAktif(array $filter, array $periode): array
    {
        $filter = array_filter($filter);
        $pintasanAktif = collect($periode['pilihan'])->contains('aktif', true);
        $chip = [];

        if (! $pintasanAktif) {
            $dari = $filter['dari'] ?? null;
            $sampai = $filter['sampai'] ?? null;
            $chip[] = [
                'label' => 'Tanggal: '.match (true) {
                    (bool) ($dari && $sampai) => FormatHasil::tanggal($dari).' sampai '.FormatHasil::tanggal($sampai),
                    (bool) $dari => 'sejak '.FormatHasil::tanggal($dari),
                    default => 'sampai '.FormatHasil::tanggal($sampai),
                },
                'kueri' => Arr::except($filter, ['dari', 'sampai']),
            ];
        }
        foreach (['jenis' => 'Jenis', 'tempat' => 'Tempat'] as $kunci => $nama) {
            if (isset($filter[$kunci])) {
                $chip[] = ['label' => $nama.': '.$filter[$kunci], 'kueri' => Arr::except($filter, [$kunci])];
            }
        }

        return [
            'chip' => $chip,
            'ada' => $filter !== [],
            'jumlah' => count($chip),
        ];
    }

    /**
     * Kalimat awam dan lebar batang untuk membandingkan hasil per baglog.
     *
     * @param  array<int, array{label: string, kg_per_baglog: ?float}>  $perbandingan
     * @return array{cukup: bool, dengan: ?array, tanpa: ?array, selisih: ?string, kalimat: string}
     */
    public function kesimpulanBaglog(array $perbandingan): array
    {
        $kelompok = collect($perbandingan)->keyBy('label');
        $dengan = $kelompok->get(RekapHasil::MEDIA[1]);
        $tanpa = $kelompok->get(RekapHasil::MEDIA[0]);
        $a = $dengan['kg_per_baglog'] ?? null;
        $b = $tanpa['kg_per_baglog'] ?? null;

        if ($a === null || $b === null || $a <= 0 || $b <= 0) {
            return [
                'cukup' => false,
                'dengan' => $dengan,
                'tanpa' => $tanpa,
                'selisih' => null,
                'kalimat' => 'Data belum cukup untuk dibandingkan.',
            ];
        }

        $terbesar = max($a, $b);
        $persen = (int) round(abs($a - $b) / $b * 100);
        [$selisih, $kalimat] = match (true) {
            $persen === 0 => ['Hampir sama', 'Hasil jamur per baglog hampir sama, dengan atau tanpa cangkang kopi.'],
            $a > $b => [$persen.'% lebih banyak', "Baglog dengan cangkang kopi menghasilkan {$persen}% lebih banyak jamur per baglog daripada baglog tanpa cangkang kopi."],
            default => [$persen.'% lebih sedikit', "Baglog dengan cangkang kopi menghasilkan {$persen}% lebih sedikit jamur per baglog. Pada catatan ini cangkang kopi belum terlihat membantu."],
        };

        return [
            'cukup' => true,
            'dengan' => $dengan + ['lebar' => round($a / $terbesar * 100, 1)],
            'tanpa' => $tanpa + ['lebar' => round($b / $terbesar * 100, 1)],
            'selisih' => $selisih,
            'kalimat' => $kalimat,
        ];
    }

    /**
     * Hasil jamur per baglog tiap bulan, dengan dan tanpa cangkang kopi. Hanya catatan yang menyebut
     * jumlah baglog yang dihitung (sama dengan perbandinganJamur), dan bulan tanpa catatan bernilai null.
     *
     * @param  array{dari?: ?string, sampai?: ?string}  $filter
     * @return array{ada: bool, label?: array<int, string>, lengkap?: array<int, string>, dengan?: array<int, ?float>, tanpa?: array<int, ?float>, terpotong?: bool}
     */
    public function baglogPerBulan(array $filter = []): array
    {
        $baris = $this->dalamRentang(PanenJamur::query(), 'tanggal_panen', $filter)
            ->where('jumlah_baglog', '>', 0)
            ->get(['tanggal_panen', 'jumlah_kg', 'jumlah_baglog', 'dengan_cangkang_kopi']);

        if ($baris->isEmpty()) {
            return ['ada' => false];
        }

        $perBulan = $baris->groupBy(fn (PanenJamur $p) => $p->tanggal_panen->format('Y-m'));
        $kunci = $perBulan->keys()->sort()->values();
        $bulan = [];
        for ($d = Carbon::parse($kunci->first().'-01'); $d->lte(Carbon::parse($kunci->last().'-01')); $d->addMonthNoOverflow()) {
            $bulan[] = $d->copy();
        }
        $terpotong = count($bulan) > self::BATAS_BULAN;
        $bulan = array_slice($bulan, -self::BATAS_BULAN);
        $lintasTahun = $bulan[0]->year !== end($bulan)->year;

        $perBaglog = function (Carbon $d, bool $denganCangkang) use ($perBulan): ?float {
            $grup = ($perBulan->get($d->format('Y-m')) ?? collect())
                ->filter(fn (PanenJamur $p) => $p->dengan_cangkang_kopi === $denganCangkang);
            $baglog = $grup->sum('jumlah_baglog');

            return $baglog > 0 ? round((float) $grup->sum('jumlah_kg') / $baglog, 2) : null;
        };

        return [
            'ada' => true,
            'label' => array_map(fn (Carbon $d) => self::BULAN_PENDEK[$d->month - 1].($lintasTahun ? $d->format(' y') : ''), $bulan),
            'lengkap' => array_map(fn (Carbon $d) => $d->translatedFormat('F Y'), $bulan),
            'dengan' => array_map(fn (Carbon $d) => $perBaglog($d, true), $bulan),
            'tanpa' => array_map(fn (Carbon $d) => $perBaglog($d, false), $bulan),
            'terpotong' => $terpotong,
        ];
    }

    /**
     * @param  array<int, string>  $kolom
     * @return array<string, array<string, float>> kolom => ['Y-m-d' => jumlah]
     */
    private function agregatHarian(Builder $query, string $kolomTanggal, array $kolom, array $filter): array
    {
        // Nama kolom berasal dari konstanta di atas, bukan dari input pengguna.
        $jumlah = implode(', ', array_map(fn (string $k) => "SUM({$k}) as {$k}", $kolom));
        $baris = $this->dalamRentang($query, $kolomTanggal, $filter)
            ->selectRaw("{$kolomTanggal} as tgl, {$jumlah}")
            ->groupBy($kolomTanggal)
            ->get();

        // Pengelompokan ulang di PHP: SQLite menyimpan tanggal sebagai teks berjam, MySQL tidak.
        return collect($kolom)->mapWithKeys(fn (string $k) => [
            $k => $baris->groupBy(fn ($b) => Carbon::parse($b->tgl)->format('Y-m-d'))
                ->map(fn (Collection $grup) => (float) $grup->sum($k))
                ->all(),
        ])->all();
    }

    /**
     * @param  array<string, array<string, float>>  $harian
     */
    private function skalaHari(array $harian, Carbon $awal, Carbon $akhir): array
    {
        $mulai = $awal->copy()->max($akhir->copy()->subDays(self::BATAS_HARI - 1));
        $hari = [];
        for ($d = $mulai->copy(); $d->lte($akhir); $d->addDay()) {
            $hari[] = $d->copy();
        }

        return $this->susunSkala(
            $hari,
            fn (Carbon $d) => $d->day.' '.self::BULAN_PENDEK[$d->month - 1],
            fn (Carbon $d) => FormatHasil::hari($d),
            fn (string $seri, Carbon $d) => $harian[$seri][$d->format('Y-m-d')] ?? 0.0,
            $mulai->gt($awal),
        );
    }

    /**
     * @param  array<string, array<string, float>>  $harian
     */
    private function skalaBulan(array $harian, Carbon $awal, Carbon $akhir): array
    {
        $bulan = [];
        for ($d = $awal->copy()->startOfMonth(); $d->lte($akhir); $d->addMonthNoOverflow()) {
            $bulan[] = $d->copy();
        }
        $terpotong = count($bulan) > self::BATAS_BULAN;
        $bulan = array_slice($bulan, -self::BATAS_BULAN);
        // Tahun ikut ditulis di sumbu hanya bila rentangnya melewati pergantian tahun.
        $lintasTahun = $bulan[0]->year !== end($bulan)->year;
        $jumlah = $this->jumlahPer($harian, 'Y-m');

        return $this->susunSkala(
            $bulan,
            fn (Carbon $d) => self::BULAN_PENDEK[$d->month - 1].($lintasTahun ? $d->format(' y') : ''),
            fn (Carbon $d) => $d->translatedFormat('F Y'),
            fn (string $seri, Carbon $d) => $jumlah[$seri][$d->format('Y-m')] ?? 0.0,
            $terpotong,
        );
    }

    /**
     * @param  array<string, array<string, float>>  $harian
     */
    private function skalaTahun(array $harian, Carbon $awal, Carbon $akhir): array
    {
        $tahun = [];
        for ($d = $awal->copy()->startOfYear(); $d->lte($akhir); $d->addYear()) {
            $tahun[] = $d->copy();
        }
        $jumlah = $this->jumlahPer($harian, 'Y');

        return $this->susunSkala(
            $tahun,
            fn (Carbon $d) => $d->format('Y'),
            fn (Carbon $d) => $d->format('Y'),
            fn (string $seri, Carbon $d) => $jumlah[$seri][$d->format('Y')] ?? 0.0,
            false,
        );
    }

    /**
     * @param  array<int, Carbon>  $titik
     */
    private function susunSkala(array $titik, callable $pendek, callable $lengkap, callable $nilai, bool $terpotong): array
    {
        $skala = [
            'label' => array_map($pendek, $titik),
            'lengkap' => array_map($lengkap, $titik),
            'terpotong' => $terpotong,
        ];
        foreach (['kopi', 'jamur', 'terjual', 'pendapatan'] as $seri) {
            $skala[$seri] = array_map(
                fn (Carbon $d) => $seri === 'pendapatan' ? (int) round($nilai($seri, $d)) : round($nilai($seri, $d), 2),
                $titik,
            );
        }

        return $skala;
    }

    /**
     * @param  array<string, array<string, float>>  $harian
     * @return array<string, array<string, float>>
     */
    private function jumlahPer(array $harian, string $format): array
    {
        return collect($harian)->map(function (array $seri) use ($format) {
            $jumlah = [];
            foreach ($seri as $tanggal => $nilai) {
                $kunci = Carbon::parse($tanggal)->format($format);
                $jumlah[$kunci] = ($jumlah[$kunci] ?? 0) + $nilai;
            }

            return $jumlah;
        })->all();
    }

    /**
     * @return ?array{persen: int, arah: string}
     */
    private function selisih(float|int $kini, float|int $lalu): ?array
    {
        // Tanpa pembanding (0) persentase tidak bermakna, jadi tidak ditampilkan.
        if ($lalu <= 0) {
            return null;
        }
        $persen = (int) round(abs($kini - $lalu) / $lalu * 100);

        return ['persen' => $persen, 'arah' => $persen === 0 ? 'tetap' : ($kini > $lalu ? 'naik' : 'turun')];
    }

    private function selisihHari(Carbon $dari, Carbon $sampai): int
    {
        return (int) round($dari->copy()->startOfDay()->diffInDays($sampai->copy()->startOfDay(), false));
    }

    // Batas memakai jam penuh karena SQLite menyimpan tanggal sebagai "Y-m-d 00:00:00" (sama seperti RekapHasil).
    private function dalamRentang(Builder $query, string $kolom, array $filter): Builder
    {
        return $query
            ->when($filter['dari'] ?? null, fn (Builder $q, string $dari) => $q->where($kolom, '>=', Carbon::parse($dari)->startOfDay()))
            ->when($filter['sampai'] ?? null, fn (Builder $q, string $sampai) => $q->where($kolom, '<=', Carbon::parse($sampai)->endOfDay()));
    }
}
