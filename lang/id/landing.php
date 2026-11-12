<?php

// Semua teks halaman depan ada di sini agar mudah diedit tanpa menyentuh tampilan.
return [
    'judul_halaman' => 'Peringatan dini longsor dan produk warga Desa Lumeneng',
    'deskripsi_meta' => 'Pantau status peringatan dini longsor Desa Lumeneng, Kecamatan Paninggaran, Kabupaten Pekalongan, dari HP.',

    'hero' => [
        'program' => 'Program KKN / Aksara Peduli',
        'judul' => 'Tahu lebih awal saat lereng Desa Lumeneng perlu diwaspadai',
        'tujuan' => 'Warga dan kader bisa melihat kondisi tanah, hujan, dan pergerakan lereng dari HP, kapan saja.',
        'tombol' => 'Lihat Status Peringatan Dini',
    ],

    'status' => [
        'judul' => 'Status saat ini',
        'belum_ada' => 'Belum ada data dari alat sensor.',
        'alat_mati' => 'Sebagian alat tidak mengirim data, jadi status bisa sudah usang.',
        'alat_mati_waspada' => 'Ada alat yang berhenti mengirim data saat kondisi Waspada. Kondisi lereng tidak diketahui, jangan anggap sudah aman. Hindari lereng.',
    ],

    'modul' => [
        'judul' => 'Yang ada di situs ini',
        'segera' => 'Segera hadir',
        'buka' => 'Buka',
    ],

    'tentang' => [
        'judul' => 'Tentang program',
        'isi' => [
            'Desa Lumeneng berada di Kecamatan Paninggaran, Kabupaten Pekalongan. Situs ini dibuat dalam program KKN / Aksara Peduli untuk membantu warga memantau risiko longsor dan memperkenalkan produk desa seperti kopi dan jamur tiram.',
            'Alat sensor di lereng mengirim data kelembapan tanah, curah hujan, dan kemiringan. Status Awas hanya muncul bila kabel alat putus atau setelah pengelola memeriksa datanya, sehingga manusia tetap ikut memutuskan.',
        ],
    ],

    'mitra' => [
        'judul' => 'Mitra',
        'daftar' => [
            ['nama' => 'Karang Taruna Giri', 'peran' => 'Kader tanggap bencana desa, 25 pemuda.'],
            ['nama' => 'Petani Muda Jaya', 'peran' => null],
        ],
    ],

    'kontak' => [
        'judul' => 'Lokasi dan nomor darurat',
        'lokasi' => 'Desa Lumeneng, Kec. Paninggaran, Kab. Pekalongan, Jawa Tengah',
        'darurat_judul' => 'Nomor darurat BPBD Kab. Pekalongan',
        // Isi HANYA setelah diverifikasi. Selama kosong, halaman menampilkan teks 'darurat_kosong'.
        'darurat_nomor' => '',
        'darurat_kosong' => 'Nomor resmi sedang diverifikasi. Saat darurat, segera menjauh dari lereng dan hubungi perangkat desa atau kader Karang Taruna terdekat.',
    ],
];
