# Web Terpadu Desa Lumeneng

Landing page, **EWS (peringatan dini longsor)**, panel **Admin**, **Toko**, dan **Edukasi** untuk
program KKN / Aksara Peduli di Desa Lumeneng, Kec. Paninggaran, Kab. Pekalongan.
Mitra EWS: Karang Taruna Giri.

> Alat bantu, bukan peringatan resmi BPBD.

Stack: Laravel, Blade, Tailwind (Vite), SQLite (lokal) / MySQL (produksi).

## Memasang di komputer sendiri

Butuh Git, PHP 8.3 atau lebih baru, Composer, dan Node.js.

1. `composer install`
2. Salin pengaturan: `cp .env.example .env` (Windows PowerShell: `copy .env.example .env`)
3. `php artisan key:generate`
4. Isi `ADMIN_EMAIL` dan `ADMIN_PASSWORD` di `.env` untuk membuat akun admin.
   Jangan pernah meng-upload berkas `.env`.
5. `php artisan migrate --seed`
6. `npm install`
7. Jalankan `npm run dev` di terminal pertama dan `php artisan serve` di terminal kedua.
8. Buka http://localhost:8000 (admin: http://localhost:8000/masuk)

Data contoh untuk dashboard EWS: `php artisan ews:simulasi --riwayat=24`

## Menambah modul

- Tempat modul Toko dan Edukasi sudah disiapkan: rute di `routes/web/`, `routes/admin/`, `routes/api/`.
- Struktur folder dan aturan kode ada di [`CLAUDE.md`](CLAUDE.md).
- Nyalakan menunya dengan `MODUL_TOKO=true` atau `MODUL_EDUKASI=true` di `.env`.
- Dengan Claude Code: `/fitur-baru toko <yang ingin dibuat>`.

## Dokumen lain

- Aturan dan API EWS: [`docs/ews/`](docs/ews/)
- Contoh kode alat ESP32: [`firmware/`](firmware/)
