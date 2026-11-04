<?php

// Pengaturan EWS. Aturan lengkap: docs/ews/ATURAN_EWS.md.
return [
    // Nilai AWAL ambang untuk seeder. Setelah itu sumbernya tabel ews_pengaturan (diubah lewat admin).
    'ambang_awal' => [
        'kelembapan_tanah' => ['waspada' => 50, 'validasi' => 80],  // %
        'curah_hujan' => ['waspada' => 10, 'validasi' => 20],       // mm/jam, acuan BMKG
        'kemiringan' => ['waspada' => 5, 'validasi' => 15],         // derajat, SEMENTARA sampai dikalibrasi
    ],

    'api_key' => env('EWS_API_KEY'),
    'alat_offline_menit' => (int) env('EWS_ALAT_OFFLINE_MENIT', 15),
    'validasi_jeda_menit' => (int) env('EWS_VALIDASI_JEDA_MENIT', 30),

    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'cooldown_menit' => (int) env('TELEGRAM_COOLDOWN_MENIT', 30),
    ],

    // Wilayah CONTOH untuk seeder; batas sebenarnya digambar admin. Urutan titik: [lintang, bujur].
    'wilayah_awal' => [
        'Lereng Kebun Kopi' => [[-7.1685, 109.5485], [-7.1685, 109.5515], [-7.1705, 109.5520], [-7.1718, 109.5500], [-7.1710, 109.5478]],
        'Jalan Desa Utama' => [[-7.1722, 109.5528], [-7.1724, 109.5552], [-7.1734, 109.5556], [-7.1738, 109.5532]],
        'Dekat Pemukiman' => [[-7.1660, 109.5448], [-7.1660, 109.5474], [-7.1676, 109.5476], [-7.1680, 109.5452]],
    ],
];
