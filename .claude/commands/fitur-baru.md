---
description: Mulai fitur baru di modul tertentu mengikuti struktur tim (contoh /fitur-baru toko keranjang belanja)
argument-hint: <modul> <deskripsi fitur>
---
Permintaan: $ARGUMENTS

Kata pertama adalah modul (landing, ews, toko, edukasi). Sisanya adalah fitur yang diinginkan.

1. Baca `CLAUDE.md` dan bagian proyek yang berhubungan dengan modul itu secara menyeluruh.
2. Pastikan branch bernama `<modul>/<topik>`; jika sedang di `main`, buatkan branch dan jelaskan perintahnya.
3. Buat HANYA di folder modul tersebut (controller, request, model, service, view, rute di
   `routes/web/<modul>.php` / `routes/admin/<modul>.php`, menu admin di
   `resources/views/admin/menu/<modul>.blade.php`, tabel berawalan `<modul>_`, tes di `tests/Feature/<Modul>`).
4. Berkas bersama (CLAUDE.md bagian 6) jangan diubah. Jika perlu, hentikan dan sarankan PR `fondasi/...`.
5. Gunakan komponen di `resources/views/components/`; jangan membuat gaya baru yang bertabrakan.
6. Jalankan `/cek`, lalu jelaskan cara mencobanya di browser (langkah bernomor).
