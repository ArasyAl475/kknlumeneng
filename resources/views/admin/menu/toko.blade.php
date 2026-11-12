{{-- Etalase produk. --}}
@include('layouts.partials.grup-menu-admin', [
    'kunci' => 'toko',
    'judul' => 'Toko Desa',
    'ikon' => 'penjualan',
    'butir' => [
        [['admin.toko.beranda', 'admin.toko.marketplace.*'], 'admin.toko.beranda', 'Marketplace', 'penjualan'],
        [['admin.toko.produk.*', 'admin.toko.foto.*'], 'admin.toko.produk.index', 'Semua produk', 'produk'],
        [['admin.toko.pengaturan.*'], 'admin.toko.pengaturan.edit', 'Info penjual', 'profil'],
        [['admin.toko.aktivitas'], 'admin.toko.aktivitas', 'Log aktivitas', 'log'],
    ],
])

{{-- Pencatatan hasil panen dan limbah kopi yang tampil di halaman publik History Limbah (/toko/hasil). --}}
@include('layouts.partials.grup-menu-admin', [
    'kunci' => 'panen',
    'judul' => 'Panen & Limbah',
    'ikon' => 'kopi',
    'butir' => [
        ['admin.toko.panen-kopi.*', 'admin.toko.panen-kopi.index', 'Panen kopi', 'kopi'],
        ['admin.toko.panen-jamur.*', 'admin.toko.panen-jamur.index', 'Panen jamur', 'jamur'],
        ['admin.toko.penjualan-jamur.*', 'admin.toko.penjualan-jamur.index', 'Penjualan jamur', 'penjualan'],
    ],
])
