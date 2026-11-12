<?php

// Pengaturan modul Toko.
return [
    // Shopee Open Platform (aplikasi jenis Seller In-House System). Kunci didapat dari Console
    // open.shopee.com dan hanya boleh ada di .env, tidak pernah di Git atau log.
    'shopee' => [
        'partner_id' => env('SHOPEE_PARTNER_ID'),
        'partner_key' => env('SHOPEE_PARTNER_KEY'),
        // true = lingkungan uji Shopee (toko uji), false = toko asli setelah aplikasi "Go Live".
        'sandbox' => (bool) env('SHOPEE_SANDBOX', true),
        // Kosongkan untuk memakai alamat bawaan; isi hanya bila Shopee mengumumkan alamat API baru.
        'host' => env('SHOPEE_HOST'),
    ],

    // Tokopedia lewat TikTok Shop Partner Center. Kunci hanya boleh ada di .env, tidak pernah di Git atau log.
    'tokopedia' => [
        'app_key' => env('TOKOPEDIA_APP_KEY'),
        'app_secret' => env('TOKOPEDIA_APP_SECRET'),
        // Khusus uji coba. Alur "Hubungkan" yang menyimpan token ke database dibuat setelah uji berhasil.
        'access_token' => env('TOKOPEDIA_ACCESS_TOKEN'),
        // Kosongkan untuk memakai alamat bawaan.
        'host' => env('TOKOPEDIA_HOST'),
    ],
];
