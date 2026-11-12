<?php

namespace App\Http\Requests\Toko;

use Illuminate\Foundation\Http\FormRequest;

/** Parameter yang ditambahkan Shopee saat mengembalikan admin dari halaman izin. */
class KembaliShopeeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:200', 'alpha_num'],
            'shop_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Izin Shopee tidak selesai: Shopee tidak mengirim kode izin. Coba hubungkan lagi.',
            'shop_id.required' => 'Izin Shopee tidak selesai: nomor toko tidak dikirim. Pastikan login dengan akun penjual, lalu coba lagi.',
        ];
    }

    /** Asal permintaan ini halaman Shopee, jadi saat galat jangan "kembali" ke sana. */
    protected function getRedirectUrl(): string
    {
        return route('admin.toko.marketplace.show', 'shopee');
    }

    public function kode(): string
    {
        return (string) $this->validated('code');
    }

    public function shopId(): int
    {
        return (int) $this->validated('shop_id');
    }
}
