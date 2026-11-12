<?php

namespace App\Services\Profil;

use App\Models\Profil\Pengaturan;
use Illuminate\Support\HtmlString;

/**
 * Isian profil desa yang siap ditampilkan. Semua pengolahan teks dan angka ada di sini,
 * agar tampilan Blade cukup menampilkan hasilnya tanpa logika.
 */
final class ProfilDesa
{
    /**
     * @param  array<string, string>  $data
     */
    private function __construct(private array $data) {}

    public static function muat(): self
    {
        return new self(Pengaturan::semua());
    }

    public function nilai(string $kunci): string
    {
        return trim($this->data[$kunci] ?? '');
    }

    public function ada(string ...$kunci): bool
    {
        foreach ($kunci as $k) {
            if ($this->nilai($k) !== '') {
                return true;
            }
        }

        return false;
    }

    public function namaDesa(): string
    {
        return 'Desa '.($this->nilai('nama_desa') ?: 'Lumeneng');
    }

    public function lokasi(): string
    {
        return collect([
            $this->nilai('kecamatan') ? 'Kec. '.$this->nilai('kecamatan') : null,
            $this->nilai('kabupaten') ? 'Kab. '.$this->nilai('kabupaten') : null,
            $this->nilai('provinsi') ?: null,
        ])->filter()->implode(', ');
    }

    public function foto(string $kunci): ?string
    {
        return PenyimpanGambar::url($this->nilai($kunci) ?: null);
    }

    // Isian berformat dari editor (sambutan, sejarah), sudah dibersihkan dan siap ditampilkan.
    public function isiKaya(string $kunci): HtmlString
    {
        return IsiKaya::tampilkan($this->nilai($kunci));
    }

    /**
     * Admin menulis satu misi per baris. Nomor atau tanda di depan baris dibuang, karena
     * halaman sudah memberi nomor sendiri dan nomor ganda membingungkan.
     *
     * @return array<int, string>
     */
    public function misi(): array
    {
        return collect(preg_split('/\R/u', $this->nilai('misi')) ?: [])
            ->map(fn (string $baris) => trim(preg_replace('/^\s*(\d+[.)]|[-•*])\s*/u', '', $baris) ?? ''))
            ->filter(fn (string $baris) => $baris !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function baris(string $kunci): array
    {
        return collect(preg_split('/\R/u', $this->nilai($kunci)) ?: [])
            ->map(fn (string $b) => trim($b))
            ->filter(fn (string $b) => $b !== '')
            ->values()
            ->all();
    }

    // Angka di antarmuka memakai titik untuk ribuan dan koma untuk desimal (3.412 dan 512,5).
    public function angka(string $kunci): ?string
    {
        $nilai = $this->nilai($kunci);

        if ($nilai === '' || ! is_numeric($nilai)) {
            return $nilai === '' ? null : $nilai;
        }

        $desimal = str_contains($nilai, '.') ? min(2, strlen(substr(strrchr($nilai, '.'), 1))) : 0;

        return number_format((float) $nilai, $desimal, ',', '.');
    }

    /**
     * Angka ringkas untuk kotak statistik. Yang belum diisi admin tidak ditampilkan.
     *
     * @return array<int, array{label: string, nilai: string, satuan: string}>
     */
    public function statistik(): array
    {
        $daftar = [
            ['jumlah_penduduk', 'Penduduk', 'jiwa'],
            ['jumlah_kk', 'Kepala keluarga', 'KK'],
            ['luas_wilayah', 'Luas wilayah', 'hektare'],
            ['jumlah_dusun', 'Dusun', 'dusun'],
            ['jumlah_rw', 'Rukun Warga', 'RW'],
            ['jumlah_rt', 'Rukun Tetangga', 'RT'],
            ['ketinggian', 'Ketinggian', 'mdpl'],
        ];

        return collect($daftar)
            ->filter(fn (array $isi) => $this->ada($isi[0]))
            ->map(fn (array $isi) => ['label' => $isi[1], 'nilai' => (string) $this->angka($isi[0]), 'satuan' => $isi[2]])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function batas(): array
    {
        return [
            'utara' => $this->nilai('batas_utara'),
            'barat' => $this->nilai('batas_barat'),
            'timur' => $this->nilai('batas_timur'),
            'selatan' => $this->nilai('batas_selatan'),
        ];
    }

    // Nomor untuk tautan tel: hanya boleh berisi angka dan tanda plus.
    public function teleponTautan(): ?string
    {
        $nomor = preg_replace('/[^\d+]/', '', $this->nilai('telepon')) ?? '';

        return $nomor !== '' ? $nomor : null;
    }

    /**
     * Tautan peta hanya ditampilkan bila benar-benar alamat web, agar isian aneh tidak
     * menjadi tautan berbahaya seperti javascript:.
     */
    public function tautanPeta(): ?string
    {
        $tautan = $this->nilai('tautan_peta');

        return preg_match('#^https?://#i', $tautan) ? $tautan : null;
    }
}
