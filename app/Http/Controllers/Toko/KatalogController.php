<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use Illuminate\Contracts\View\View;

class KatalogController extends Controller
{
    /**
     * Tampilkan katalog produk Toko Desa (Koci Circular).
     */
    public function index(): View
    {
        // Data produk sirkular Koci (kopi, jamur, baglog, dan kompos residu)
        $produk = [
            [
                'id' => 1,
                'nama' => 'Jamur Tiram Putih',
                'kategori' => 'jamur',
                'label' => 'Organik Grade A',
                'subkategori' => 'Grade A',
                'takaran' => '500 g • Panen Harian',
                'harga' => 14000,
                'stok_teks' => 'Ready Stock',
                'stok_status' => 'ready',
                'gambar' => 'gambar/toko/jamur-tiram.jpg',
                'deskripsi' => 'Jamur tiram segar dibudidayakan menggunakan media cangkang dan limbah kopi sirkular Desa Lumeneng.',
            ],
            [
                'id' => 2,
                'nama' => 'Baglog Jamur Koci',
                'kategori' => 'jamur',
                'label' => 'Bibit Sirkular',
                'subkategori' => 'Baglog Siap Tumbuh',
                'takaran' => '1.2 kg / baglog',
                'harga' => 6500,
                'stok_teks' => '85 unit',
                'stok_status' => 'ready',
                'gambar' => 'gambar/toko/baglog-koci.jpg',
                'deskripsi' => 'Media tanam baglog berkualitas tinggi berbahan baku cangkang kopi fermentasi siap tumbuh.',
            ],
            [
                'id' => 3,
                'nama' => 'Kopi Eco-Wash',
                'kategori' => 'kopi',
                'label' => 'Single Origin',
                'subkategori' => 'Kemasan 500g',
                'takaran' => '250 g • Roast Biji',
                'harga' => 65000,
                'stok_teks' => 'Stok Terbatas',
                'stok_status' => 'terbatas',
                'gambar' => 'gambar/toko/kopi-puntang.jpg',
                'deskripsi' => 'Kopi Robusta & Arabika konservasi lereng bukit Lumeneng dengan proses eco-wash ramah air.',
            ],
            [
                'id' => 4,
                'nama' => 'Kompos Sirkular',
                'kategori' => 'jamur',
                'label' => 'Kasgot & Residu',
                'subkategori' => 'Pupuk Kompos Koci',
                'takaran' => '5 kg • Nutrisi Tinggi',
                'harga' => 25000,
                'stok_teks' => 'Ready Stock',
                'stok_status' => 'ready',
                'gambar' => 'gambar/toko/kompos-sirkular.jpg',
                'deskripsi' => 'Pupuk organik hasil residu spent baglog dan kasgot kaya unsur hara untuk tanaman pekarangan.',
            ],
        ];

        $filterChips = [
            'Semua',
            'Grade A',
            'Kemasan 500g',
            'Baglog Siap Tumbuh',
            'Pupuk Kompos Koci',
        ];

        $ringkasan = [
            'total_panen_jamur' => PanenJamur::query()->sum('jumlah_kg') ?: 8.5,
            'total_panen_kopi' => PanenKopi::query()->sum('jumlah_kg') ?: 1250,
        ];

        return view('toko.katalog', [
            'produk' => $produk,
            'filterChips' => $filterChips,
            'ringkasan' => $ringkasan,
        ]);
    }
}
