<?php

namespace App\Http\Requests\Toko;

use App\Models\Toko\Produk;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class FotoProdukRequest extends FormRequest
{
    // Batas unggah sebenarnya ditentukan php.ini server; aturan mengikuti yang lebih kecil
    // supaya pesan galat menyebut angka yang benar, bukan "gagal diunggah" yang membingungkan.
    public static function batasKb(): int
    {
        return (int) min(4096, floor(UploadedFile::getMaxFilesize() / 1024));
    }

    public static function batasTeks(): string
    {
        $kb = self::batasKb();

        return $kb >= 1024 ? rtrim(rtrim(number_format($kb / 1024, 1, ',', ''), '0'), ',').' MB' : $kb.' KB';
    }

    /**
     * Jenis berkas yang benar-benar bisa dibaca GD di server ini; semuanya diubah ke WebP/JPG saat disimpan.
     * Aturan "image" bawaan Laravel juga menerima HEIC, padahal GD tidak bisa membacanya, jadi daftar ini
     * yang menentukan. AVIF hanya diterima bila GD server mendukungnya, agar tidak lolos lalu gagal dibaca.
     *
     * @return array<int, string>
     */
    public static function jenisDiterima(): array
    {
        return array_merge(['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], function_exists('imagecreatefromavif') ? ['avif'] : []);
    }

    public static function jenisTeks(): string
    {
        $nama = collect(self::jenisDiterima())
            ->reject(fn (string $jenis) => $jenis === 'jpeg')
            ->map(fn (string $jenis) => match ($jenis) {
                'jpg' => 'JPG (.jpg, .jpeg, .jpe, .jfif)',
                'webp' => 'WebP',
                default => strtoupper($jenis),
            })
            ->values();

        return $nama->slice(0, -1)->implode(', ').', atau '.$nama->last();
    }

    /**
     * Isi atribut accept di form: jenis MIME ditambah ekstensinya, karena pemilih berkas Windows kadang
     * menyembunyikan .jfif atau .jpe bila hanya diberi MIME. Server tetap menilai dari isi berkas.
     * HEIC sengaja tidak dicantumkan agar iPhone otomatis mengubah fotonya menjadi JPG saat dipilih.
     */
    public static function accept(): string
    {
        return implode(',', [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp', 'image/avif',
            '.jpg', '.jpeg', '.jpe', '.jfif', '.pjpeg', '.pjp', '.png', '.webp', '.gif', '.bmp', '.dib', '.avif',
        ]);
    }

    public function rules(): array
    {
        return [
            'foto' => ['required', 'array', 'min:1', 'max:'.Produk::MAKS_FOTO],
            // Batas dimensi menjaga memori saat GD memperkecil foto beresolusi sangat tinggi. Di browser
            // yang mendukung, foto sudah diperkecil otomatis sebelum dikirim (resources/js/toko/unggah-foto.js).
            'foto.*' => ['image', 'mimes:'.implode(',', self::jenisDiterima()), 'max:'.self::batasKb(), 'dimensions:max_width=6000,max_height=6000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Produk $produk */
                $produk = $this->route('produk');
                $sisa = Produk::MAKS_FOTO - $produk->foto()->count();

                if (count((array) $this->file('foto', [])) > $sisa) {
                    $validator->errors()->add('foto', $sisa > 0
                        ? "Hanya bisa menambah {$sisa} foto lagi (maksimal ".Produk::MAKS_FOTO.' foto per produk).'
                        : 'Produk ini sudah punya '.Produk::MAKS_FOTO.' foto. Hapus salah satu dulu.');
                }
            },
        ];
    }

    public function messages(): array
    {
        $jenis = 'Berkas harus berupa foto ('.self::jenisTeks().').';
        // HEIC disebut khusus karena itu format bawaan kamera iPhone dan paling sering membuat admin bingung.
        $heic = ' Foto HEIC dari iPhone: pilih lewat browser di iPhone (otomatis diubah ke JPG), atau ubah Pengaturan > Kamera > Format ke "Paling Kompatibel".';

        $pesan = [
            'foto.required' => 'Pilih minimal satu foto.',
            'foto.*.image' => $jenis,
            'foto.*.mimes' => $jenis.$heic,
            'foto.*.max' => 'Ukuran foto maksimal '.self::batasTeks().'. Buka halaman ini lewat browser HP terbaru (Chrome atau Safari) agar foto diperkecil otomatis.',
            'foto.*.uploaded' => 'Foto gagal diunggah. Coba unggah lagi.',
            'foto.*.dimensions' => 'Resolusi foto terlalu besar. Buka halaman ini lewat browser HP terbaru agar foto diperkecil otomatis, atau kecilkan dulu lewat WhatsApp lalu simpan lagi.',
        ];

        // PHP memberi kode penyebab kegagalan unggah; tanpa ini semua kegagalan tampak seperti "terlalu besar",
        // padahal bisa juga sinyal putus atau folder sementara server yang tidak bisa ditulisi.
        // Ditaruh di depan karena Laravel memakai pesan pertama yang cocok, dan "foto.*" juga cocok.
        $khusus = [];
        foreach ((array) $this->file('foto', []) as $i => $berkas) {
            if ($berkas instanceof UploadedFile && ! $berkas->isValid()) {
                $khusus["foto.{$i}.uploaded"] = self::pesanGagalUnggah($berkas);
            }
        }

        return $khusus + $pesan;
    }

    public static function pesanGagalUnggah(UploadedFile $berkas): string
    {
        $kode = $berkas->getError();
        $nama = '"'.$berkas->getClientOriginalName().'"';

        return match ($kode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Foto {$nama} lebih besar dari batas server (".self::batasTeks().'). Buka halaman ini lewat browser HP terbaru agar foto diperkecil otomatis.',
            UPLOAD_ERR_PARTIAL => "Foto {$nama} hanya terkirim sebagian, biasanya karena sinyal putus. Coba unggah lagi.",
            UPLOAD_ERR_NO_FILE => 'Tidak ada foto yang terkirim. Pilih fotonya lagi.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => "Foto {$nama} sudah sampai, tetapi server tidak bisa menyimpannya sementara (kode {$kode}). Ini masalah pengaturan server, bukan fotonya. Kabari pengelola situs.",
            default => "Foto {$nama} tidak diterima server (kode {$kode}). Coba unggah lagi; bila tetap gagal, kabari pengelola situs.",
        };
    }
}
