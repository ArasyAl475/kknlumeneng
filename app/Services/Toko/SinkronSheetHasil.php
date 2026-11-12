<?php

namespace App\Services\Toko;

use App\Models\Toko\Aktivitas;
use App\Models\Toko\SinkronSheet;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Sinkron dua arah catatan hasil panen <-> Google Sheets (satu tab per jenis catatan).
 *
 * Susunan tab: kolom A = ID (diisi sistem), lalu kolom data sesuai IsianHasilPanen::JENIS, kolom terakhir = Status.
 *
 * Aturan per baris (SinkronSheet.hash = sidik isi saat terakhir sama di kedua sisi):
 *  - ID kosong            -> catatan baru; ID ditulis balik ke Sheet.
 *  - hanya Sheet berubah  -> catatan di sistem diperbarui.
 *  - hanya sistem berubah -> baris Sheet diperbarui.
 *  - keduanya berubah     -> data sistem menang, baris diberi status "Konflik".
 *  - baris salah          -> tidak mengubah apa pun; alasannya ditulis di kolom Status, isian dibiarkan agar bisa diperbaiki.
 *  - baris dihapus di Sheet -> catatan TIDAK dihapus; baris dikembalikan. Penghapusan hanya lewat panel admin.
 *  - catatan dihapus di panel admin -> barisnya dibuang dari Sheet.
 *
 * Perubahan di sistem dan penulisan ke Sheet berada dalam satu transaksi: kalau menulis ke Sheet gagal,
 * perubahan di sistem dibatalkan juga, jadi putaran berikutnya mengulang dengan keadaan yang sama
 * (tidak ada catatan ganda akibat ID gagal ditulis balik).
 */
class SinkronSheetHasil
{
    public const STATUS_OK = 'Tersinkron';

    private const KUNCI_KUNCI = 'toko_sheets.sinkron';

    public function __construct(private GoogleSheets $sheets, private IsianHasilPanen $isian) {}

    /**
     * @return array<int, string>
     */
    public static function kepala(string $jenis): array
    {
        return ['ID', ...array_column(IsianHasilPanen::JENIS[$jenis]['kolom'], 'judul'), 'Status'];
    }

    public static function huruf(string $jenis): string
    {
        return Coordinate::stringFromColumnIndex(count(self::kepala($jenis)));
    }

    public static function namaTab(string $jenis): string
    {
        return IsianHasilPanen::JENIS[$jenis]['label'];
    }

    /**
     * @return ?array<string, array<string, mixed>> hitungan per jenis, atau null bila putaran lain masih berjalan
     */
    public function sinkronkan(): ?array
    {
        // Kunci mencegah dua putaran (jadwal + perintah manual) menulis bersamaan dan membuat catatan ganda.
        $hasil = Cache::lock(self::KUNCI_KUNCI, 120)->get(function (): array {
            $jenis = IsianHasilPanen::kunci();
            $baca = $this->sheets->bacaTab(array_map(fn (string $j) => "'".self::namaTab($j)."'!A1:".self::huruf($j), $jenis));

            $hasil = [];
            DB::transaction(function () use ($jenis, $baca, &$hasil) {
                $tulis = [];
                foreach ($jenis as $i => $j) {
                    [$rencana, $hasil[$j]] = $this->prosesTab($j, $baca[$i] ?? []);
                    if ($rencana !== null) {
                        $tulis[] = $rencana;
                    }
                }
                if ($tulis !== []) {
                    $this->sheets->tulis($tulis);
                }
            });

            $this->catatAktivitas($hasil);

            return $hasil;
        });

        return $hasil === false ? null : $hasil;
    }

    /**
     * @param  array<int, array<int, mixed>>  $baris  isi tab, baris pertama = judul kolom
     * @return array{0: ?array{range: string, values: array}, 1: array<string, mixed>}
     */
    private function prosesTab(string $jenis, array $baris): array
    {
        $def = $this->isian->definisi($jenis);
        $label = $def['label'];
        $kepala = self::kepala($jenis);
        $jumlahKolom = count($kepala);
        $jumlahField = count($def['kolom']);

        if ($this->normalisasi($this->lebarkan($baris[0] ?? [], $jumlahKolom)) !== $this->normalisasi($kepala)) {
            return [null, ['galat' => "Judul kolom di tab \"{$label}\" tidak sesuai. Jalankan: php artisan toko:sheets-siapkan"]];
        }

        $data = array_slice($baris, 1);
        if (count($data) > (int) config('toko_sheets.maks_baris_tab')) {
            return [null, ['galat' => "Tab \"{$label}\" berisi terlalu banyak baris; dilewati."]];
        }

        $model = $def['model'];
        $catatan = $model::query()->get()->keyBy('id');
        $sidik = SinkronSheet::query()->where('jenis', $jenis)->pluck('hash', 'catatan_id');

        $hitung = ['dibuat' => 0, 'diperbarui' => 0, 'dari_sistem' => 0, 'konflik' => 0, 'salah' => 0, 'dikembalikan' => 0, 'dibuang' => 0];
        $keluar = [];
        $dilihat = [];
        $sidikBaru = [];

        foreach ($data as $mentahBaris) {
            $sel = $this->lebarkan($mentahBaris, $jumlahKolom);
            if ($this->kosong($sel)) {
                continue;
            }

            $nilai = array_slice($sel, 1, $jumlahField);
            $statusLama = trim((string) $sel[$jumlahKolom - 1]);
            $id = null;

            if (! $this->kosong([$sel[0]])) {
                $id = $this->bacaId($sel[0]);
                $galatId = match (true) {
                    $id === null => 'ID tidak valid. Biarkan kolom ID kosong untuk catatan baru.',
                    isset($dilihat[$id]) => "ID {$id} muncul lebih dari sekali. Kosongkan kolom ID pada baris salinan.",
                    default => null,
                };

                if ($galatId === null && ! $catatan->has($id)) {
                    if ($sidik->has($id)) {
                        $hitung['dibuang']++; // sudah dihapus lewat panel admin

                        continue;
                    }
                    $galatId = "ID {$id} tidak dikenal. Kosongkan kolom ID untuk catatan baru.";
                }

                if ($galatId !== null) {
                    $keluar[] = [$sel[0], ...$nilai, 'Perlu diperbaiki: '.$galatId];
                    $hitung['salah']++;

                    continue;
                }
                $dilihat[$id] = true;
            }

            $rekam = $id === null ? null : $catatan[$id];

            if ($rekam !== null) {
                $tampilDb = $this->selDariCatatan($def, $rekam);
                if ($this->normalisasi($nilai) === $this->normalisasi($tampilDb)) {
                    $sidikBaru[$id] = $this->sidik($def, $rekam);
                    $keluar[] = [$id, ...$tampilDb, $this->statusTahan($statusLama)];

                    continue;
                }
            }

            [$bersih, $galat] = $this->isian->periksaIsian($def, array_combine(array_keys($def['kolom']), $nilai));
            if ($galat !== []) {
                $keluar[] = [$rekam?->getKey() ?? '', ...$nilai, 'Perlu diperbaiki: '.implode(' ', $galat)];
                $hitung['salah']++;

                continue;
            }

            $sidikSheet = $this->sidik($def, $bersih);

            if ($rekam === null) {
                $baru = $model::query()->create($bersih);
                $dilihat[$baru->getKey()] = true;
                $sidikBaru[$baru->getKey()] = $sidikSheet;
                $keluar[] = [$baru->getKey(), ...$this->selDariCatatan($def, $baru), self::STATUS_OK];
                $hitung['dibuat']++;

                continue;
            }

            $sidikDb = $this->sidik($def, $rekam);
            $sidikTerakhir = $sidik[$id] ?? null;

            if ($sidikSheet === $sidikDb) {
                $sidikBaru[$id] = $sidikDb;
                $keluar[] = [$id, ...$this->selDariCatatan($def, $rekam), self::STATUS_OK];
            } elseif ($sidikTerakhir === $sidikDb) {
                $rekam->update($bersih);
                $sidikBaru[$id] = $sidikSheet;
                $keluar[] = [$id, ...$this->selDariCatatan($def, $rekam), self::STATUS_OK];
                $hitung['diperbarui']++;
            } elseif ($sidikTerakhir === $sidikSheet) {
                $sidikBaru[$id] = $sidikDb;
                $keluar[] = [$id, ...$this->selDariCatatan($def, $rekam), self::STATUS_OK];
                $hitung['dari_sistem']++;
            } else {
                $sidikBaru[$id] = $sidikDb;
                $keluar[] = [$id, ...$this->selDariCatatan($def, $rekam), 'Konflik: isian di Sheet tidak dipakai karena catatan ini juga diubah di sistem.'];
                $hitung['konflik']++;
            }
        }

        // Catatan yang belum punya baris: dibuat dari panel admin, atau barisnya dihapus di Sheet.
        foreach ($catatan as $id => $rekam) {
            if (isset($dilihat[$id])) {
                continue;
            }
            $dikembalikan = $sidik->has($id);
            $sidikBaru[$id] = $this->sidik($def, $rekam);
            $keluar[] = [$id, ...$this->selDariCatatan($def, $rekam), $dikembalikan
                ? 'Dikembalikan: menghapus baris di Sheet tidak menghapus catatan. Hapus lewat panel admin.'
                : self::STATUS_OK];
            $hitung[$dikembalikan ? 'dikembalikan' : 'dari_sistem']++;
        }

        $this->simpanSidik($jenis, $sidik, $sidikBaru, $catatan->keys()->all());

        $sekarang = array_values(array_map(fn (array $b) => $this->normalisasi($this->lebarkan($b, $jumlahKolom)), array_filter(
            array_map(fn (array $b) => $this->lebarkan($b, $jumlahKolom), $data),
            fn (array $b) => ! $this->kosong($b),
        )));
        $diharap = array_map(fn (array $b) => $this->normalisasi($b), $keluar);

        if ($sekarang === $diharap) {
            return [null, $hitung];
        }

        // Baris sisa di bawah ikut dikosongkan dalam satu permintaan yang sama, supaya tidak ada baris lama yang tertinggal.
        $total = max(count($keluar), count($data));
        $nilaiTulis = array_pad($keluar, $total, array_fill(0, $jumlahKolom, ''));

        return [['range' => "'{$label}'!A2:".self::huruf($jenis).($total + 1), 'values' => $nilaiTulis], $hitung];
    }

    /**
     * @param  array<int, string>  $kunciCatatan
     */
    private function simpanSidik(string $jenis, $lama, array $baru, array $kunciCatatan): void
    {
        $berubah = [];
        foreach ($baru as $id => $hash) {
            if (($lama[$id] ?? null) !== $hash) {
                $berubah[] = ['jenis' => $jenis, 'catatan_id' => $id, 'hash' => $hash, 'created_at' => now(), 'updated_at' => now()];
            }
        }
        foreach (array_chunk($berubah, 500) as $bagian) {
            SinkronSheet::query()->upsert($bagian, ['jenis', 'catatan_id'], ['hash', 'updated_at']);
        }

        // Sidik milik catatan yang sudah dihapus dari sistem tidak dibutuhkan lagi.
        $usang = array_diff($lama->keys()->all(), $kunciCatatan);
        foreach (array_chunk($usang, 500) as $bagian) {
            SinkronSheet::query()->where('jenis', $jenis)->whereIn('catatan_id', $bagian)->delete();
        }
    }

    private function catatAktivitas(array $hasil): void
    {
        $bagian = [];
        foreach ($hasil as $jenis => $h) {
            $ringkas = array_filter([
                ($h['dibuat'] ?? 0) ? "{$h['dibuat']} baru" : null,
                ($h['diperbarui'] ?? 0) ? "{$h['diperbarui']} diubah" : null,
                ($h['konflik'] ?? 0) ? "{$h['konflik']} konflik" : null,
            ]);
            if ($ringkas !== []) {
                $bagian[] = mb_strtolower(self::namaTab($jenis)).': '.implode(', ', $ringkas);
            }
        }

        if ($bagian !== []) {
            Aktivitas::query()->create([
                'user_id' => null,
                'nama_admin' => 'Sinkron Google Sheets',
                'aksi' => 'sinkron_sheets',
                'keterangan' => 'Perubahan dari Google Sheets — '.implode('; ', $bagian).'.',
            ]);
        }
    }

    // ───────────────────────────── Bahan penyusun ─────────────────────────────

    /**
     * Pesan Konflik/Dikembalikan dibiarkan sampai baris itu berubah lagi; status lain kembali jadi "Tersinkron".
     */
    private function statusTahan(string $statusLama): string
    {
        return Str::startsWith($statusLama, ['Konflik', 'Dikembalikan']) ? $statusLama : self::STATUS_OK;
    }

    private function bacaId(mixed $nilai): ?int
    {
        if (is_int($nilai) || (is_float($nilai) && floor($nilai) === $nilai)) {
            return $nilai > 0 ? (int) $nilai : null;
        }

        return is_string($nilai) && ctype_digit(trim($nilai)) && (int) trim($nilai) > 0 ? (int) trim($nilai) : null;
    }

    /**
     * @param  array<int, mixed>  $baris
     * @return array<int, mixed>
     */
    private function lebarkan(array $baris, int $lebar): array
    {
        return array_slice(array_pad(array_values($baris), $lebar, ''), 0, $lebar);
    }

    /**
     * @param  array<int, mixed>  $sel
     */
    private function kosong(array $sel): bool
    {
        return collect($sel)->every(fn ($n) => $n === null || trim((string) $n) === '');
    }

    /**
     * Bentuk sama untuk dibandingkan: angka 100 dan 100.0 dianggap sama, spasi pinggir diabaikan.
     *
     * @param  array<int, mixed>  $sel
     * @return array<int, string>
     */
    private function normalisasi(array $sel): array
    {
        return array_map(function (mixed $n): string {
            return match (true) {
                $n === null => '',
                is_bool($n) => $n ? '1' : '0',
                is_int($n) || is_float($n) => rtrim(rtrim(number_format((float) $n, 6, '.', ''), '0'), '.') ?: '0',
                default => trim((string) $n),
            };
        }, array_values($sel));
    }

    /**
     * Isi catatan sebagai sel Sheet: tanggal d-m-Y (teks), angka sebagai angka, media sebagai kata.
     *
     * @return array<int, mixed>
     */
    private function selDariCatatan(array $def, Model $rekam): array
    {
        $sel = [];
        foreach ($def['kolom'] as $field => $k) {
            $nilai = $rekam->getAttribute($field);
            $sel[] = match (true) {
                $nilai === null || $nilai === '' => '',
                $k['tipe'] === 'tanggal' => $nilai instanceof CarbonInterface ? $nilai->format('d-m-Y') : date('d-m-Y', strtotime((string) $nilai)),
                $k['tipe'] === 'desimal' => round((float) $nilai, 2),
                $k['tipe'] === 'bilangan' => (int) $nilai,
                $k['tipe'] === 'media' => RekapHasil::MEDIA[(int) $nilai],
                default => (string) $nilai,
            };
        }

        return $sel;
    }

    /**
     * Sidik isi catatan yang sama untuk catatan di database dan data bersih dari Sheet.
     *
     * @param  Model|array<string, mixed>  $sumber
     */
    private function sidik(array $def, Model|array $sumber): string
    {
        $isi = [];
        foreach ($def['kolom'] as $field => $k) {
            $nilai = $sumber instanceof Model ? $sumber->getAttribute($field) : ($sumber[$field] ?? null);
            $isi[] = match (true) {
                $nilai === null || $nilai === '' => '',
                $k['tipe'] === 'tanggal' => $nilai instanceof CarbonInterface ? $nilai->format('Y-m-d') : date('Y-m-d', strtotime((string) $nilai)),
                $k['tipe'] === 'desimal' => number_format((float) $nilai, 2, '.', ''),
                $k['tipe'] === 'bilangan' => (string) (int) $nilai,
                $k['tipe'] === 'media' => (string) (int) filter_var($nilai, FILTER_VALIDATE_BOOLEAN),
                default => trim((string) $nilai),
            };
        }

        return sha1((string) json_encode($isi));
    }
}
