<?php

// Pengaturan sistem EWS.
return [
    // Nilai AWAL ambang batas (dipakai seeder). Setelah itu admin mengubahnya lewat halaman admin.
    // Kelembapan tanah (%)
    'batas_waspada'  => 50,   // kelembapan tanah >= ini = Waspada
    'batas_validasi' => 80,   // kelembapan tanah >= ini = laporan menunggu validasi admin
    // Curah hujan (mm per jam). Acuan BMKG: lebat 10-20 mm/jam, sangat lebat > 20 mm/jam.
    'batas_hujan_waspada'  => 10,
    'batas_hujan_validasi' => 20,
    // Kemiringan (derajat). ANGKA AWAL SEMENTARA: sesuaikan dengan hasil kalibrasi alat di lapangan.
    'batas_miring_waspada'  => 5,
    'batas_miring_validasi' => 15,

    // Alat dianggap "tidak mengirim data" jika sudah sekian menit tidak ada kiriman (mis. modem/sinyal mati).
    'alat_offline_menit' => (int) env('EWS_ALAT_OFFLINE_MENIT', 15),
    // Setelah admin memutuskan sebuah laporan, laporan baru untuk wilayah yang sama ditahan sekian menit
    // (alat asli mengirim data terus-menerus, jangan membanjiri admin dengan laporan yang sama).
    'validasi_jeda_menit' => (int) env('EWS_VALIDASI_JEDA_MENIT', 30),

    // true = halaman /admin dan /admin/simulator wajib login. Set true di .env setelah akun admin dibuat.
    'admin_wajib_login' => (bool) env('EWS_ADMIN_LOGIN', false),

    // Diisi lewat file .env (jangan tulis token langsung di sini)
    'telegram_token'          => env('TELEGRAM_BOT_TOKEN'),
    'telegram_chat_id'        => env('TELEGRAM_CHAT_ID'),
    // Jeda minimal antar pesan "perlu validasi" per lokasi. 0 = tanpa jeda (cocok untuk simulasi).
    'telegram_cooldown_menit' => (int) env('TELEGRAM_COOLDOWN_MENIT', 0),

    // Kunci rahasia yang harus dikirim alat (hardware) lewat header X-API-KEY
    'api_key' => env('EWS_API_KEY'),

    // Akun admin yang dibuat seeder
    'admin_email'    => env('ADMIN_EMAIL', 'admin@lumeneng.test'),
    'admin_password' => env('ADMIN_PASSWORD'),

    // Wilayah awal untuk seeder (DUMMY). Wilayah baru ditambah admin lewat peta.
    // Urutan titik: [lintang, bujur].
    'wilayah_awal' => [
        'Lereng Kebun Kopi' => [[-7.1685, 109.5485], [-7.1685, 109.5515], [-7.1705, 109.5520], [-7.1718, 109.5500], [-7.1710, 109.5478]],
        'Jalan Desa Utama'  => [[-7.1722, 109.5528], [-7.1724, 109.5552], [-7.1734, 109.5556], [-7.1738, 109.5532]],
        'Dekat Pemukiman'   => [[-7.1660, 109.5448], [-7.1660, 109.5474], [-7.1676, 109.5476], [-7.1680, 109.5452]],
    ],
];
