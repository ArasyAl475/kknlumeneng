# Web Terpadu Desa Lumeneng

Landing page, **Profil Desa & Berita**, **EWS (peringatan dini longsor)**, panel **Admin**, **Toko**, dan **Edukasi** untuk
program KKN / Aksara Peduli di Desa Lumeneng, Kec. Paninggaran, Kab. Pekalongan.
Mitra EWS: Karang Taruna Giri.

> Alat bantu, bukan peringatan resmi BPBD.

Stack: Laravel, Blade, Tailwind (Vite), SQLite (lokal) / MySQL (produksi).

## Memasang di komputer sendiri

Butuh: Git, PHP 8.3+ (dengan ekstensi `gd`), Composer, dan Node.js.

**1. Ambil kode dan pasang**

```
git clone https://github.com/ArasyAl475/kknlumeneng.git
cd kknlumeneng
composer install
copy .env.example .env
php artisan key:generate
```

`copy` untuk Windows PowerShell; di Git Bash/Mac/Linux pakai `cp .env.example .env`.

**2. Isi akun admin** di berkas `.env`: `ADMIN_EMAIL` dan `ADMIN_PASSWORD`. Jangan pernah meng-upload `.env`.

**3. Siapkan database dan tampilan**

```
php artisan migrate --seed
php artisan storage:link
npm install
```

**4. Jalankan**

```
composer run dev
```

Buka http://localhost:8000 (admin: http://localhost:8000/masuk). Berhenti: tekan `Ctrl+C`.

**Opsional: isi data contoh** (hanya untuk latihan di komputer sendiri)

```
php artisan db:seed --class=ProfilContohSeeder
php artisan db:seed --class=EdukasiContohSeeder
php artisan db:seed --class=TokoContohHasilSeeder
php artisan ews:simulasi --riwayat=24
```

**Setiap habis `git pull`**

```
composer install
npm install
php artisan migrate
```

Memasang di hosting: [`docs/DEPLOY.md`](docs/DEPLOY.md).

## Menambah modul

- Tempat modul Toko dan Edukasi sudah disiapkan: rute di `routes/web/`, `routes/admin/`, `routes/api/`.
- Struktur folder dan aturan kode ada di [`CLAUDE.md`](CLAUDE.md).
- Menu Profil Desa, Berita, Toko, Edukasi, dan History Limbah tampil secara bawaan. Untuk menyembunyikan, isi `MODUL_PROFIL=false`, `MODUL_TOKO=false`, atau `MODUL_EDUKASI=false` di `.env`.
- Dengan Claude Code: `/fitur-baru toko <yang ingin dibuat>`.

## Dokumen lain

- Aturan dan API EWS: [`docs/ews/`](docs/ews/)
- Contoh kode alat ESP32: [`firmware/`](firmware/)
