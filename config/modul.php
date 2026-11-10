<?php

// Daftar modul untuk menu publik, kartu landing, dan sidebar admin.
// Modul dengan 'aktif' false atau rutenya belum ada tampil sebagai "segera hadir".
// 'pola' (opsional): pola nama rute yang membuat menu atas menyala, untuk modul dengan beberapa halaman.
return [
    // Profil desa ditaruh paling depan karena itulah yang pertama dicari pengunjung situs desa.
    'profil' => [
        'aktif' => (bool) env('MODUL_PROFIL', true),
        'nama' => 'Profil Desa',
        'rute' => 'profil.index',
        'pola' => 'profil.*',
        'ikon' => 'desa',
        'deskripsi' => 'Sejarah, visi misi, dan susunan pemerintahan Desa Lumeneng.',
    ],
    // Berita dikelola dari menu admin Profil Desa dan ikut bendera MODUL_PROFIL.
    'berita' => [
        'aktif' => (bool) env('MODUL_PROFIL', true),
        'nama' => 'Berita',
        'rute' => 'berita.index',
        'pola' => 'berita.*',
        'ikon' => 'berita',
        'deskripsi' => 'Kabar kegiatan, pengumuman, dan artikel terbaru dari desa.',
    ],
    // Galeri berisi foto dokumentasi kegiatan (terhubung ke berita) dan foto desa mandiri.
    'galeri' => [
        'aktif' => (bool) env('MODUL_PROFIL', true),
        'nama' => 'Galeri',
        'rute' => 'galeri.index',
        'pola' => 'galeri.*',
        'ikon' => 'galeri',
        'deskripsi' => 'Foto kegiatan warga dan suasana Desa Lumeneng.',
    ],
    'ews' => [
        'aktif' => (bool) env('MODUL_EWS', true),
        'nama' => 'Peringatan Dini',
        'rute' => 'ews.index',
        'ikon' => 'waspada',
        'deskripsi' => 'Pantau kondisi lereng Desa Lumeneng: kelembapan tanah, curah hujan, dan pergerakan tanah.',
    ],
    'toko' => [
        // Bawaan true karena modulnya sudah rampung. .env hanya dipakai untuk mematikan.
        'aktif' => (bool) env('MODUL_TOKO', true),
        'nama' => 'Toko Desa',
        'rute' => 'toko.index',
        'ikon' => 'toko',
        'deskripsi' => 'Kopi, jamur tiram, dan produk warga Desa Lumeneng.',
    ],
    'edukasi' => [
        'aktif' => (bool) env('MODUL_EDUKASI', true),
        'nama' => 'Edukasi',
        'rute' => 'edukasi.index',
        'ikon' => 'buku',
        'deskripsi' => 'Bacaan tentang mitigasi longsor dan ekonomi sirkular.',
    ],
    // Limbah kopi dicatat bersama panen lewat panel admin, lalu tampil di halaman Hasil Panen (toko.hasil).
    // Kartu landing membuka halaman Hasil Panen. Pencatatan tetap lewat admin (menu Panen kopi).
    // Ikut bendera MODUL_TOKO. Tampil juga di menu atas agar warga bisa langsung menuju halaman limbah.
    'limbah' => [
        'aktif' => (bool) env('MODUL_TOKO', true),
        'nama' => 'History Limbah',
        'rute' => 'toko.hasil',
        'ikon' => 'tanah',
        'deskripsi' => 'Pantau limbah cangkang kopi dari panen warga, lengkap dengan grafik dan perbandingan hasil jamur.',
    ],
];
