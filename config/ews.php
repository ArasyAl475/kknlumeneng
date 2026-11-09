<?php

// Pengaturan EWS. Aturan lengkap: docs/ews/ATURAN_EWS.md.
return [
    // Nilai AWAL ambang untuk seeder. Setelah itu sumbernya tabel ews_pengaturan (diubah lewat admin).
    'ambang_awal' => [
        'kelembapan_tanah' => ['waspada' => 50, 'validasi' => 80],  // %
        'intensitas_hujan' => ['waspada' => 40, 'validasi' => 70],  // %, SEMENTARA sampai sensor hujan dikalibrasi
        'kemiringan' => ['waspada' => 5, 'validasi' => 15],         // derajat, SEMENTARA sampai dikalibrasi
    ],

    'api_key' => env('EWS_API_KEY'),
    'alat_offline_menit' => (int) env('EWS_ALAT_OFFLINE_MENIT', 15),
    'validasi_jeda_menit' => (int) env('EWS_VALIDASI_JEDA_MENIT', 30),

    // Jalur kedua penerimaan data alat (selain POST /api/sensor). Alat menerbitkan ke
    // "{topik_awalan}/{wilayah_id}/sensor"; perintah `php artisan ews:mqtt` yang berlangganan.
    'mqtt' => [
        'aktif' => (bool) env('EWS_MQTT_AKTIF', false),
        'host' => env('EWS_MQTT_HOST'),
        'port' => (int) env('EWS_MQTT_PORT', 8883),
        'tls' => (bool) env('EWS_MQTT_TLS', true),
        // Opsional: berkas sertifikat CA (PEM), berguna di Windows/XAMPP yang belum punya daftar CA.
        'ca_file' => env('EWS_MQTT_CA_FILE'),
        'username' => env('EWS_MQTT_USERNAME'),
        'password' => env('EWS_MQTT_PASSWORD'),
        // Harus tetap sama dan unik: broker menyimpan pesan yang terlewat untuk id ini.
        'client_id' => env('EWS_MQTT_CLIENT_ID', 'ews-lumeneng-web'),
        'topik_awalan' => env('EWS_MQTT_TOPIK_AWALAN', 'ews'),
        // Pesan dari wilayah yang sama lebih rapat dari ini diabaikan (alat rusak yang membanjiri).
        'jeda_minimal_detik' => (int) env('EWS_MQTT_JEDA_MINIMAL_DETIK', 5),
        // true = dijalankan penjadwal tiap menit (hosting hanya punya cron). false = jalankan terus-menerus.
        'jadwal' => (bool) env('EWS_MQTT_JADWAL', false),
    ],

    // Wilayah CONTOH untuk seeder; batas sebenarnya digambar admin. Urutan titik: [lintang, bujur].
    'wilayah_awal' => [
        'Lereng Kebun Kopi' => [[-7.1685, 109.5485], [-7.1685, 109.5515], [-7.1705, 109.5520], [-7.1718, 109.5500], [-7.1710, 109.5478]],
        'Jalan Desa Utama' => [[-7.1722, 109.5528], [-7.1724, 109.5552], [-7.1734, 109.5556], [-7.1738, 109.5532]],
        'Dekat Pemukiman' => [[-7.1660, 109.5448], [-7.1660, 109.5474], [-7.1676, 109.5476], [-7.1680, 109.5452]],
    ],
];
