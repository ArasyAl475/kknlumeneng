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

    // Data awal untuk seeder, dari lokasi alat sebenarnya. Urutan titik: [lintang, bujur].
    // Wilayah = EWS pintar yang mengirim data (ID-nya dipasang di ESP32).
    'wilayah_awal' => [
        'Titik Area 2' => [
            'poligon' => [[-7.186448, 109.591973], [-7.186262, 109.591157], [-7.187830, 109.591357], [-7.187723, 109.591529]],
            'lokasi_alat' => [-7.186625, 109.591824],
        ],
    ],

    // Titik yang tidak mengirim data ke web. jenis: alarm | rawan. Titik rawan longsor ditambah admin sendiri.
    'titik_awal' => [
        'Titik Area 1' => [
            'jenis' => 'alarm',
            'poligon' => [[-7.185772, 109.588701], [-7.185060, 109.587750], [-7.185356, 109.587502], [-7.186111, 109.588447]],
            'lokasi_alat' => [-7.185390, 109.588069],
        ],
    ],
];
