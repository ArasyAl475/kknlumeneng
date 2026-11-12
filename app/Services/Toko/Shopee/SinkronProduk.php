<?php

namespace App\Services\Toko\Shopee;

use App\Models\Toko\Produk;
use App\Models\Toko\TautanProduk;
use App\Models\Toko\TokenShopee;
use App\Services\Toko\DaftarMarketplace;
use App\Services\Toko\PenyimpanProduk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Menarik nama, harga, dan deskripsi produk dari Shopee ke toko desa.
 *
 * Aturan yang disepakati: produk Shopee yang belum ada langsung dibuat dan tampil, dan data Shopee
 * selalu menimpa nama, harga, dan deskripsi di sini. Foto, stok, dan status tampil tidak disentuh
 * untuk produk yang sudah ada. Produk yang sengaja dihapus admin tidak dihidupkan lagi.
 */
class SinkronProduk
{
    public const JALUR_DAFTAR = '/api/v2/product/get_item_list';

    public const JALUR_RINCIAN = '/api/v2/product/get_item_base_info';

    private const UKURAN_HALAMAN = 100;

    // Batas Shopee untuk satu panggilan rincian.
    private const UKURAN_RINCIAN = 50;

    // Pagar pengaman agar toko dengan data aneh tidak membuat sinkron berjalan tanpa akhir.
    private const MAKS_ITEM = 1000;

    private const KUNCI_KUNCI = 'toko-sinkron-shopee';

    public function __construct(
        private readonly KlienShopee $klien,
        private readonly KoneksiShopee $koneksi,
    ) {}

    /**
     * @return array{dibuat: int, diperbarui: int, dilewati: int, gagal: int}
     */
    public function jalankan(): array
    {
        // Mencegah dua sinkron berjalan bersamaan (klik ganda admin atau bentrok dengan jadwal malam).
        $kunci = Cache::lock(self::KUNCI_KUNCI, 600);

        if (! $kunci->get()) {
            throw new GalatShopee('Sinkron Shopee sedang berjalan. Tunggu beberapa menit lalu coba lagi.');
        }

        try {
            return $this->proses($this->tokenSiap());
        } finally {
            $kunci->release();
        }
    }

    /**
     * @return array{dibuat: int, diperbarui: int, dilewati: int, gagal: int}
     */
    private function proses(TokenShopee $token): array
    {
        $hasil = ['dibuat' => 0, 'diperbarui' => 0, 'dilewati' => 0, 'gagal' => 0];

        $tautan = TautanProduk::query()->where('marketplace', 'shopee')->get();
        $petaTautan = [];

        // Tautan manual belum punya id_eksternal; nomor produknya dibaca dari alamat bila bisa.
        foreach ($tautan as $baris) {
            $id = $baris->id_eksternal ?? self::idDariUrl($baris->url);

            if ($id !== null) {
                $petaTautan[$id] ??= $baris;
            }
        }

        // withTrashed agar produk yang dihapus admin dikenali dan dilewati, bukan dibuat ulang.
        $petaProduk = Produk::withTrashed()->whereIn('id', $tautan->pluck('produk_id'))->get()->keyBy('id');

        foreach (array_chunk($this->ambilDaftarId($token), self::UKURAN_RINCIAN) as $kelompok) {
            foreach ($this->ambilRincian($token, $kelompok) as $item) {
                $this->simpanItem($item, $token, $petaTautan, $petaProduk, $hasil);
            }
        }

        return $hasil;
    }

    private function tokenSiap(): TokenShopee
    {
        $token = $this->koneksi->tersimpan();

        if (! $token) {
            throw new GalatShopee('Toko Shopee belum terhubung. Hubungkan dulu di panel admin.');
        }

        if (! $token->masihBerlaku()) {
            throw new GalatShopee('Izin Shopee sudah habis. Tekan "Hubungkan ulang" di panel admin.');
        }

        $this->koneksi->perpanjangBilaPerlu();

        return $token->refresh();
    }

    /**
     * @return list<string>
     */
    private function ambilDaftarId(TokenShopee $token): array
    {
        $semua = [];
        $offset = 0;

        do {
            $isi = $this->klien->panggilToko('GET', self::JALUR_DAFTAR, $token->access_token, $token->shop_id, [
                'offset' => $offset,
                'page_size' => self::UKURAN_HALAMAN,
                // Hanya produk yang sedang dijual; yang diturunkan atau diblokir tidak perlu tampil di etalase.
                'item_status' => 'NORMAL',
            ])['response'] ?? [];

            foreach ($isi['item'] ?? [] as $item) {
                if (isset($item['item_id'])) {
                    $semua[] = (string) $item['item_id'];
                }
            }

            $adaLagi = (bool) ($isi['has_next_page'] ?? false);
            $offset = (int) ($isi['next_offset'] ?? 0);
        } while ($adaLagi && count($semua) < self::MAKS_ITEM);

        return array_slice($semua, 0, self::MAKS_ITEM);
    }

    /**
     * @param  list<string>  $idItem
     * @return list<array<string, mixed>>
     */
    private function ambilRincian(TokenShopee $token, array $idItem): array
    {
        $isi = $this->klien->panggilToko('GET', self::JALUR_RINCIAN, $token->access_token, $token->shop_id, [
            'item_id_list' => implode(',', $idItem),
        ])['response'] ?? [];

        return $isi['item_list'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, TautanProduk>  $petaTautan
     * @param  Collection<int, Produk>  $petaProduk
     * @param  array{dibuat: int, diperbarui: int, dilewati: int, gagal: int}  $hasil
     */
    private function simpanItem(array $item, TokenShopee $token, array $petaTautan, $petaProduk, array &$hasil): void
    {
        $id = (string) ($item['item_id'] ?? '');
        $nama = mb_substr(trim((string) ($item['item_name'] ?? '')), 0, 120);

        if ($id === '' || $nama === '') {
            $hasil['gagal']++;

            return;
        }

        $tautan = $petaTautan[$id] ?? null;
        $produk = $tautan ? $petaProduk->get($tautan->produk_id) : null;

        if ($produk?->trashed()) {
            $hasil['dilewati']++;

            return;
        }

        try {
            DB::transaction(function () use ($item, $token, $id, $nama, $tautan, $produk) {
                $produk ??= new Produk(['slug' => PenyimpanProduk::slugUnik($nama), 'tampil' => true, 'tersedia' => true, 'urutan' => 0]);

                $produk->nama = $nama;
                $produk->deskripsi = self::deskripsi($item);

                // Shopee tidak selalu mengirim harga (mis. produk bervarian). Harga lama dijaga
                // daripada dikosongkan diam-diam.
                $harga = self::harga($item);

                if ($harga !== null) {
                    $produk->harga = $harga;
                }

                $produk->save();

                $this->simpanTautan($produk, $tautan, $id, $token->shop_id);
            });
        } catch (Throwable $galat) {
            // Satu produk bermasalah tidak boleh menghentikan produk lainnya. Hanya pesan yang dicatat
            // (bukan report) agar jejak galat tidak ikut mencetak argumen seperti token.
            Log::warning("Sinkron Shopee: item {$id} gagal disimpan. ".$galat::class.': '.$galat->getMessage());
            $hasil['gagal']++;

            return;
        }

        $hasil[$produk === null ? 'dibuat' : 'diperbarui']++;
    }

    private function simpanTautan(Produk $produk, ?TautanProduk $tautan, string $id, int $shopId): void
    {
        $url = "https://shopee.co.id/product/{$shopId}/{$id}";

        if ($tautan !== null) {
            // Alamat yang diisi admin dipertahankan (sering lebih rapi); hanya penanda sinkron yang diisi.
            $tautan->update(['id_eksternal' => $id, 'terakhir_sinkron' => now()]);

            return;
        }

        if (! DaftarMarketplace::tautanSah('shopee', $url)) {
            throw new GalatShopee('Alamat produk yang dibentuk tidak lolos pemeriksaan domain.');
        }

        $produk->tautan()->create([
            'marketplace' => 'shopee',
            'url' => $url,
            'id_eksternal' => $id,
            'sumber' => TautanProduk::SUMBER_API,
            'terakhir_sinkron' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function harga(array $item): ?int
    {
        $harga = $item['price_info'][0]['current_price'] ?? null;

        return is_numeric($harga) && $harga >= 0 ? (int) round((float) $harga) : null;
    }

    /**
     * Shopee punya dua bentuk deskripsi: teks biasa, atau "extended" berupa daftar bagian teks dan gambar.
     * Gambar dibuang karena toko ini hanya menampilkan teks (foto dikelola terpisah).
     *
     * @param  array<string, mixed>  $item
     */
    public static function deskripsi(array $item): ?string
    {
        if (($item['description_type'] ?? '') === 'extended') {
            $bagian = collect($item['extended_description']['field_list'] ?? [])
                ->filter(fn ($b) => ($b['field_type'] ?? '') === 'text' && filled($b['text'] ?? null))
                ->pluck('text')
                ->all();

            $teks = trim(implode("\n\n", $bagian));
        } else {
            $teks = trim((string) ($item['description'] ?? ''));
        }

        return $teks === '' ? null : mb_substr($teks, 0, 10000);
    }

    /** Membaca nomor produk dari alamat Shopee panjang (…-i.TOKO.PRODUK atau /product/TOKO/PRODUK). */
    public static function idDariUrl(string $url): ?string
    {
        return preg_match('~(?:/product/\d+/|i\.\d+\.)(\d+)~', $url, $cocok) === 1 ? $cocok[1] : null;
    }
}
