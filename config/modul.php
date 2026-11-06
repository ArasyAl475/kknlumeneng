<?php

// Daftar modul untuk menu publik, kartu landing, dan sidebar admin.
// Modul dengan 'aktif' false atau rutenya belum ada tampil sebagai "segera hadir".
return [
    'ews' => [
        'aktif' => (bool) env('MODUL_EWS', true),
        'nama' => 'Peringatan Dini',
        'rute' => 'ews.index',
        'ikon' => 'waspada',
        'deskripsi' => 'Pantau kondisi lereng Desa Lumeneng: kelembapan tanah, curah hujan, dan pergerakan tanah.',
    ],
    'toko' => [
        'aktif' => (bool) env('MODUL_TOKO', false),
        'nama' => 'Toko Desa',
        'rute' => 'toko.index',
        'ikon' => 'toko',
        'deskripsi' => 'Kopi, jamur tiram, dan produk warga Desa Lumeneng.',
    ],
    'edukasi' => [
        'aktif' => (bool) env('MODUL_EDUKASI', false),
        'nama' => 'Edukasi',
        'rute' => 'edukasi.index',
        'ikon' => 'buku',
        'deskripsi' => 'Bacaan tentang mitigasi longsor dan ekonomi sirkular.',
    ],
    // Limbah kopi dicatat bersama panen lewat panel admin, lalu tampil di halaman Hasil Panen (toko.hasil).
    // Kartu landing membuka halaman Hasil Panen. Pencatatan tetap lewat admin (menu Panen kopi).
    // Ikut bendera MODUL_TOKO. 'menu_publik' => false: tidak dobel di menu atas (sudah ada Toko Desa).
    'limbah' => [
        'aktif' => (bool) env('MODUL_TOKO', false),
        'nama' => 'Catat Limbah',
        'rute' => 'toko.hasil',
        'ikon' => 'tanah',
        'menu_publik' => false,
        'deskripsi' => 'Pantau limbah cangkang kopi dari panen warga, lengkap dengan grafik dan perbandingan hasil jamur.',
    ],
];
