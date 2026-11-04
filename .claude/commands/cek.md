---
description: Cek mutu kode (format, tes, build) dan jelaskan hasilnya dengan bahasa sederhana
---
Jalankan berurutan. Jika ada yang gagal, perbaiki penyebabnya lalu ulangi langkah itu.

1. `vendor/bin/pint --test` (jika gagal: jalankan `vendor/bin/pint`, lalu ulangi).
2. `php artisan test`
3. `npm run build`

Setelah itu tulis ringkasan pendek untuk pemula: apa yang lulus, apa yang gagal, penyebabnya,
dan apa yang sudah diperbaiki. Jangan menyembunyikan kegagalan dan jangan menghapus/melemahkan
tes agar lulus.
