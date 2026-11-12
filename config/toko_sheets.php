<?php

// Sinkron dua arah hasil panen <-> Google Sheets. Panduan pemasangan: docs/toko/SINKRON_SHEETS.md.
return [
    // Saklar utama. Mati bila belum ada kunci Google; jadwal tetap jalan tapi tidak melakukan apa pun.
    'aktif' => (bool) env('TOKO_SHEETS_AKTIF', false),

    // ID ada di alamat Sheet: docs.google.com/spreadsheets/d/<ID>/edit
    'spreadsheet_id' => env('TOKO_SHEETS_ID'),

    // LOKASI berkas kunci akun layanan (JSON), bukan isinya. Simpan di luar folder yang di-commit.
    'kunci_json' => env('TOKO_SHEETS_KUNCI'),

    // Jadwal berjalan tiap 10 detik; batas pendek agar satu putaran macet tidak menumpuk
    // (selama satu putaran belum selesai, putaran berikutnya dilewati).
    'timeout_detik' => 15,

    // Pengaman: tab dengan baris lebih banyak dari ini dilewati, bukan dibaca separuh.
    'maks_baris_tab' => 20000,
];
