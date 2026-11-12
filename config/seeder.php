<?php

// Isian kosong di .env (SEEDER_DATA_CONTOH=) dianggap tidak diisi, supaya salinan .env.example tidak mematikan data contoh di lokal.
$pilihan = env('SEEDER_DATA_CONTOH');

return [
    // Data CONTOH (nama perangkat, berita, nomor telepon karangan, dst.) ikut terisi saat `db:seed`.
    // Nilai awal: aktif di lokal dan testing, NONAKTIF di server (APP_ENV=production) agar situs sungguhan
    // tidak menampilkan data karangan. Ubah lewat SEEDER_DATA_CONTOH di .env bila memang perlu (mis. demo).
    'contoh' => ($pilihan === null || $pilihan === '')
        ? env('APP_ENV', 'production') !== 'production'
        : filter_var($pilihan, FILTER_VALIDATE_BOOLEAN),
];
