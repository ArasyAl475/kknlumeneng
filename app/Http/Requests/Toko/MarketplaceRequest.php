<?php

namespace App\Http\Requests\Toko;

use Illuminate\Foundation\Http\FormRequest;

class MarketplaceRequest extends FormRequest
{
    public function rules(): array
    {
        $kunci = (string) $this->route('marketplace');

        return [
            'url_toko' => ['nullable', 'string', 'max:500', ProdukRequest::aturanTautan($kunci)],
            'url_dasbor' => ['nullable', 'string', 'max:500', ProdukRequest::aturanTautan($kunci)],
            'aktif' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['url_toko' => 'alamat toko', 'url_dasbor' => 'alamat dasbor penjual'];
    }

    /**
     * @return array{url_toko: ?string, url_dasbor: ?string, aktif: bool}
     */
    public function dataMarketplace(): array
    {
        $bersihkan = fn (?string $url) => filled($url) ? trim($url) : null;

        return [
            'url_toko' => $bersihkan($this->validated('url_toko')),
            'url_dasbor' => $bersihkan($this->validated('url_dasbor')),
            'aktif' => (bool) $this->validated('aktif'),
        ];
    }
}
