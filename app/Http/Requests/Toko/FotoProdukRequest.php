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

    public function rules(): array
    {
        return [
            'foto' => ['required', 'array', 'min:1', 'max:'.Produk::MAKS_FOTO],
            // Batas dimensi menjaga memori saat GD memperkecil foto beresolusi sangat tinggi.
            'foto.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::batasKb(), 'dimensions:max_width=6000,max_height=6000'],
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
        return [
            'foto.required' => 'Pilih minimal satu foto.',
            'foto.*.image' => 'Berkas harus berupa foto (JPG, PNG, atau WebP).',
            'foto.*.mimes' => 'Berkas harus berupa foto (JPG, PNG, atau WebP).',
            'foto.*.max' => 'Ukuran foto maksimal '.self::batasTeks().'.',
            'foto.*.uploaded' => 'Foto gagal diunggah. Kemungkinan ukurannya lebih dari '.self::batasTeks().'.',
            'foto.*.dimensions' => 'Resolusi foto terlalu besar. Kecilkan dulu, misalnya kirim lewat WhatsApp lalu simpan lagi.',
        ];
    }
}
