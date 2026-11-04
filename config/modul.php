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
];
