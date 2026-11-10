<?php

namespace App\Http\Requests\Profil;

use App\Services\Profil\BagianProfil;
use App\Services\Profil\IsiKaya;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class PengaturanRequest extends FormRequest
{
    // Isian yang ditulis lewat editor berformat (boleh tebal, daftar, foto).
    public const ISIAN_KAYA = ['sambutan', 'sejarah'];

    private const ANGKA_BULAT = ['jumlah_penduduk', 'jumlah_kk', 'jumlah_dusun', 'jumlah_rw', 'jumlah_rt', 'ketinggian'];

    public function bagian(): string
    {
        return (string) $this->route('bagian');
    }

    /**
     * Admin terbiasa menulis "3.412" dan "512,5". Angka diubah ke bentuk yang dipahami
     * basis data sebelum diperiksa, agar penulisan gaya Indonesia tidak ditolak.
     */
    protected function prepareForValidation(): void
    {
        if ($this->bagian() !== 'wilayah') {
            return;
        }

        $rapi = [];

        foreach (self::ANGKA_BULAT as $kolom) {
            if (is_string($this->input($kolom))) {
                $rapi[$kolom] = str_replace(['.', ' '], '', $this->input($kolom));
            }
        }

        $luas = $this->input('luas_wilayah');
        if (is_string($luas) && str_contains($luas, ',')) {
            $rapi['luas_wilayah'] = str_replace(['.', ' ', ','], ['', '', '.'], $luas);
        }

        $this->merge($rapi);
    }

    public function rules(): array
    {
        $teks = fn (int $maks) => ['nullable', 'string', "max:{$maks}"];
        $bulat = ['nullable', 'integer', 'min:0', 'max:1000000'];

        return match ($this->bagian()) {
            'identitas' => [
                'nama_desa' => ['required', 'string', 'max:60'],
                'tagline' => $teks(150),
                'kecamatan' => $teks(60),
                'kabupaten' => $teks(60),
                'provinsi' => $teks(60),
                'nama_kepala_desa' => $teks(100),
                'sambutan' => $teks(30000),
            ],
            'sejarah' => [
                'sejarah' => $teks(200000),
                'visi' => $teks(500),
                'misi' => $teks(3000),
            ],
            'wilayah' => [
                'luas_wilayah' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
                'ketinggian' => ['nullable', 'integer', 'min:0', 'max:5000'],
                'jumlah_penduduk' => $bulat,
                'jumlah_kk' => $bulat,
                'jumlah_dusun' => $bulat,
                'jumlah_rw' => $bulat,
                'jumlah_rt' => $bulat,
                'batas_utara' => $teks(100),
                'batas_selatan' => $teks(100),
                'batas_timur' => $teks(100),
                'batas_barat' => $teks(100),
            ],
            'kontak' => [
                'alamat_kantor' => $teks(255),
                'telepon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
                'email' => ['nullable', 'email', 'max:100'],
                'jam_pelayanan' => $teks(500),
                'tautan_peta' => ['nullable', 'url:http,https', 'max:500'],
            ],
            default => [],
        };
    }

    public function messages(): array
    {
        return [
            'telepon.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, tanda +, -, dan tanda kurung.',
            'tautan_peta.url' => 'Tautan peta harus diawali https://. Salin dari tombol Bagikan di Google Maps.',
            '*.integer' => ':Attribute harus berupa angka bulat, tanpa huruf.',
            '*.numeric' => ':Attribute harus berupa angka. Pakai koma untuk desimal, misalnya 512,5.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_desa' => 'nama desa',
            'nama_kepala_desa' => 'nama kepala desa',
            'luas_wilayah' => 'luas wilayah',
            'jumlah_penduduk' => 'jumlah penduduk',
            'jumlah_kk' => 'jumlah KK',
            'jumlah_dusun' => 'jumlah dusun',
            'jumlah_rw' => 'jumlah RW',
            'jumlah_rt' => 'jumlah RT',
            'batas_utara' => 'batas utara',
            'batas_selatan' => 'batas selatan',
            'batas_timur' => 'batas timur',
            'batas_barat' => 'batas barat',
            'alamat_kantor' => 'alamat kantor',
            'jam_pelayanan' => 'jam pelayanan',
            'tautan_peta' => 'tautan peta',
        ];
    }

    /**
     * @return array<string, ?string>
     */
    public function dataTeks(): array
    {
        $foto = BagianProfil::ambil($this->bagian())['foto'];
        $buang = array_merge($foto, array_map(fn (string $k) => "hapus_{$k}", $foto));

        $data = array_map(
            fn (mixed $nilai) => $nilai === null ? null : trim((string) $nilai),
            array_diff_key($this->validated(), array_flip($buang)),
        );

        // Isian dari editor berformat disimpan dalam bentuk yang sudah dibersihkan.
        foreach (self::ISIAN_KAYA as $kolom) {
            if (array_key_exists($kolom, $data)) {
                $data[$kolom] = IsiKaya::bersihkan($data[$kolom]);
            }
        }

        return $data;
    }

    /**
     * @return array<string, ?UploadedFile>
     */
    public function fotoBaru(): array
    {
        return collect(BagianProfil::ambil($this->bagian())['foto'])
            ->mapWithKeys(fn (string $kolom) => [$kolom => $this->file($kolom)])
            ->all();
    }

    /**
     * @return array<string, bool>
     */
    public function hapusFoto(): array
    {
        return collect(BagianProfil::ambil($this->bagian())['foto'])
            ->mapWithKeys(fn (string $kolom) => [$kolom => $this->boolean("hapus_{$kolom}")])
            ->all();
    }
}
