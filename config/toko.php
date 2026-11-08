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
];
