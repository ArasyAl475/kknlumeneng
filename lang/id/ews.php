<?php

return [
    'saran' => [
        'hijau' => 'Semua sensor normal. Tetap pantau halaman ini saat hujan.',
        'kuning' => 'Tanah mulai jenuh air, hujan deras, atau lereng mulai bergerak. Hindari lereng dan siapkan jalur evakuasi.',
        'merah' => 'Menjauh dari lereng sekarang dan ikuti arahan Karang Taruna serta perangkat desa.',
        'kosong' => 'Menunggu data pertama dari alat sensor.',
    ],
    'alat_mati' => 'Alat tidak mengirim data: :nama (terakhir :waktu WIB).',
    'alat_mati_waspada' => 'Alat di :nama berhenti mengirim data (terakhir :waktu WIB) saat kondisi Waspada. Kondisi lereng sekarang tidak diketahui, jadi jangan anggap sudah aman. Hindari lereng.',
    'disclaimer' => 'Ini alat bantu pemantauan warga, bukan peringatan resmi BPBD. Saat ragu, utamakan keselamatan dan ikuti arahan perangkat desa.',
    'level' => [
        'kosong' => 'Tidak ada data',
        'informasi' => 'Hanya informasi',
        0 => 'Normal',
        1 => 'Waspada',
        2 => 'Lewat batas',
    ],
];
