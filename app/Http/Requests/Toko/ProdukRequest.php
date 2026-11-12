<?php

namespace App\Http\Requests\Toko;

use App\Models\Toko\Produk;
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
            // Harga wajib karena pembeli memutuskan membeli dari harga. Varian berharga tidak menggantikannya:
            // harga ini jadi harga dasar bila varian dihapus atau semua variannya tidak tersedia.
            'harga' => ['required', 'integer', 'min:1', 'max:100000000'],
            'tersedia' => ['required', 'boolean'],
            'tampil' => ['required', 'boolean'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
            'kembali' => ['nullable', Rule::in(DaftarMarketplace::KUNCI)],

            'tautan' => ['nullable', 'array'],

            'varian' => ['nullable', 'array', 'max:'.self::MAKS_BARIS],
            'varian.*.nama' => ['nullable', 'string', 'max:60', 'required_with:varian.*.harga'],
            'varian.*.harga' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'varian.*.tersedia' => ['nullable', 'boolean'],

            'utama' => ['nullable', 'array'],
        ];

        foreach (DaftarMarketplace::KUNCI as $kunci) {
            $aturan["tautan.{$kunci}"] = ['nullable', 'string', 'max:500', self::aturanTautan($kunci)];
        }

        // Isian utama semuanya boleh kosong: tidak semua berlaku untuk tiap produk (jamur tidak punya biji-bijian).
        foreach (array_keys(Produk::SPESIFIKASI_UTAMA) as $kunci) {
            $aturan["utama.{$kunci}"] = ['nullable', 'string', 'max:200'];
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
        ];

        foreach (DaftarMarketplace::KUNCI as $kunci) {
            $nama["tautan.{$kunci}"] = 'tautan '.DaftarMarketplace::nama($kunci);
        }

        foreach (Produk::SPESIFIKASI_UTAMA as $kunci => $label) {
            $nama["utama.{$kunci}"] = mb_strtolower($label);
        }

        return $nama;
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama produk wajib diisi.',
            'nama.max' => 'Nama produk terlalu panjang. Tulis paling banyak 120 huruf.',
            'harga.required' => 'Harga wajib diisi. Tulis angkanya saja, misalnya 25000.',
            'harga.min' => 'Harga harus lebih dari 0.',
            'harga.max' => 'Harga terlalu besar. Periksa lagi jumlah nolnya.',
            'varian.*.nama.required_with' => 'Varian yang diberi harga juga perlu diberi nama, misalnya "250 g".',
            'harga.integer' => 'Harga hanya boleh berisi angka.',
        ];
    }

    /**
     * Baris varian dan isian spesifikasi yang dibiarkan kosong di form dibuang.
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
            // Hanya isian utama yang disimpan, urut tetap. Baris tambahan bebas sudah ditiadakan, jadi
            // sisa baris tambahan lama ikut terbuang saat produk disimpan ulang.
            'spesifikasi' => collect(Produk::SPESIFIKASI_UTAMA)
                ->map(fn (string $label, string $kunci) => ['label' => $label, 'nilai' => trim((string) ($data['utama'][$kunci] ?? ''))])
                ->filter(fn (array $b) => $b['nilai'] !== '')
                ->values()->all(),
        ];
    }
}
