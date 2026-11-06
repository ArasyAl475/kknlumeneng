<?php

namespace App\Http\Requests\Toko;

use App\Services\Toko\DaftarMarketplace;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdukRequest extends FormRequest
{
    public const MAKS_BARIS = 20;

    /**
     * Admin awam sering mengetik "25.000" atau "Rp 25.000". Hanya "Rp", titik, dan spasi yang
     * dibuang; huruf lain dibiarkan supaya tetap ditolak, bukan diam-diam menjadi 0.
     */
    protected function prepareForValidation(): void
    {
        $angka = function ($nilai) {
            $bersih = is_string($nilai) ? preg_replace('/rp|[\s.]/i', '', $nilai) : $nilai;

            return $bersih === '' ? null : $bersih;
        };

        $this->merge([
            'harga' => $angka($this->input('harga')),
            'varian' => collect($this->input('varian', []))
                ->map(fn ($baris) => is_array($baris) ? ['harga' => $angka($baris['harga'] ?? null)] + $baris : $baris)
                ->all(),
        ]);
    }

    public function rules(): array
    {
        $aturan = [
            'nama' => ['required', 'string', 'max:120'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'harga' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'tersedia' => ['required', 'boolean'],
            'tampil' => ['required', 'boolean'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
            'kembali' => ['nullable', Rule::in(DaftarMarketplace::KUNCI)],

            'tautan' => ['nullable', 'array'],

            'varian' => ['nullable', 'array', 'max:'.self::MAKS_BARIS],
            'varian.*.nama' => ['nullable', 'string', 'max:60', 'required_with:varian.*.harga'],
            'varian.*.harga' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'varian.*.tersedia' => ['nullable', 'boolean'],

            'spesifikasi' => ['nullable', 'array', 'max:'.self::MAKS_BARIS],
            'spesifikasi.*.label' => ['nullable', 'string', 'max:60', 'required_with:spesifikasi.*.nilai'],
            'spesifikasi.*.nilai' => ['nullable', 'string', 'max:200', 'required_with:spesifikasi.*.label'],
        ];

        foreach (DaftarMarketplace::KUNCI as $kunci) {
            $aturan["tautan.{$kunci}"] = ['nullable', 'string', 'max:500', self::aturanTautan($kunci)];
        }

        return $aturan;
    }

    public static function aturanTautan(string $kunci): Closure
    {
        return function (string $kolom, mixed $nilai, Closure $gagal) use ($kunci): void {
            if (! DaftarMarketplace::tautanSah($kunci, trim((string) $nilai))) {
                $gagal('Tautan '.DaftarMarketplace::nama($kunci).' harus diawali https:// dan berasal dari '
                    .DaftarMarketplace::domainUtama($kunci).'. Salin tautan dari tombol Bagikan di aplikasi '.DaftarMarketplace::nama($kunci).'.');
            }
        };
    }

    public function attributes(): array
    {
        $nama = [
            'nama' => 'nama produk',
            'harga' => 'harga',
            'urutan' => 'urutan tampil',
            'varian.*.nama' => 'nama varian',
            'varian.*.harga' => 'harga varian',
            'spesifikasi.*.label' => 'nama spesifikasi',
            'spesifikasi.*.nilai' => 'isi spesifikasi',
        ];

        foreach (DaftarMarketplace::KUNCI as $kunci) {
            $nama["tautan.{$kunci}"] = 'tautan '.DaftarMarketplace::nama($kunci);
        }

        return $nama;
    }

    public function messages(): array
    {
        return [
            'varian.*.nama.required_with' => 'Varian yang diberi harga juga perlu diberi nama, misalnya "250 g".',
            'spesifikasi.*.label.required_with' => 'Isi nama spesifikasinya juga, misalnya "Berat".',
            'spesifikasi.*.nilai.required_with' => 'Isi spesifikasinya juga, misalnya "250 g".',
            'harga.integer' => 'Harga hanya boleh berisi angka.',
        ];
    }

    /**
     * Baris varian dan spesifikasi yang dibiarkan kosong di form dibuang.
     *
     * @return array{nama: string, deskripsi: ?string, harga: ?int, tersedia: bool, tampil: bool, urutan: int,
     *     spesifikasi: array<int, array{label: string, nilai: string}>, tautan: array<string, ?string>,
     *     varian: array<int, array{nama: string, harga: ?int, tersedia: bool}>}
     */
    public function dataProduk(): array
    {
        $data = $this->validated();

        return [
            'nama' => trim($data['nama']),
            'deskripsi' => filled($data['deskripsi'] ?? null) ? trim($data['deskripsi']) : null,
            'harga' => isset($data['harga']) ? (int) $data['harga'] : null,
            'tersedia' => (bool) $data['tersedia'],
            'tampil' => (bool) $data['tampil'],
            'urutan' => (int) ($data['urutan'] ?? 0),
            'tautan' => $data['tautan'] ?? [],
            'varian' => collect($data['varian'] ?? [])
                ->filter(fn (array $b) => filled($b['nama'] ?? null))
                ->map(fn (array $b) => [
                    'nama' => trim($b['nama']),
                    'harga' => isset($b['harga']) ? (int) $b['harga'] : null,
                    'tersedia' => (bool) ($b['tersedia'] ?? true),
                ])
                ->values()->all(),
            'spesifikasi' => collect($data['spesifikasi'] ?? [])
                ->filter(fn (array $b) => filled($b['label'] ?? null) && filled($b['nilai'] ?? null))
                ->map(fn (array $b) => ['label' => trim($b['label']), 'nilai' => trim($b['nilai'])])
                ->values()->all(),
        ];
    }
}
