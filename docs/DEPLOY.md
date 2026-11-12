# Panduan hosting (server)

Ditulis untuk pemula. Jalankan perintah satu per satu di folder proyek di server.
Kebutuhan server: PHP 8.3 ke atas, MySQL, Composer, Node.js (hanya untuk membangun tampilan; boleh dibangun di laptop lalu folder `public/build` diunggah).

## 1. Isi berkas `.env` di server
Salin `.env.example` menjadi `.env`, lalu isi minimal ini:

| Isian | Nilai |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | alamat situs, mis. `https://lumeneng.example` |
| `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | data MySQL dari hosting |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | akun admin pertama. Kata sandi minimal 12 karakter, ada huruf dan angka, tanpa kata umum (`admin`, `password`, `lumeneng`). Bila lemah, proses seed berhenti dengan pesan galat. |
| `EWS_API_KEY` | teks acak panjang (32 karakter); sama dengan yang dipasang di ESP32 |
| `SEEDER_DATA_CONTOH` | **kosongkan** (di server nilainya otomatis nonaktif) |

Jangan pernah menempel isi `.env` ke chat atau Git.

## 2. Perintah pemasangan (urut)
1. `composer install --no-dev --optimize-autoloader`: memasang pustaka PHP.
2. `php artisan key:generate`: membuat kunci aplikasi (sekali saja, hanya bila `APP_KEY` masih kosong).
3. `php artisan migrate --seed --force`: membuat semua tabel lalu mengisi data awal. `--force` wajib di server.
4. `php artisan storage:link`: membuat tautan agar foto unggahan dan foto potensi bisa dibuka dari web.
5. `npm ci` lalu `npm run build`: membangun tampilan (atau unggah folder `public/build` dari laptop).
6. `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`: mempercepat situs. Ulangi setelah `.env` diubah.

Seed aman dijalankan ulang kapan saja (`php artisan db:seed --force`): data yang sudah ada atau sudah diubah admin tidak ditimpa.

## 3. Yang diisi seeder di server (data asli saja)
| Modul | Terisi otomatis |
|---|---|
| Admin | satu akun admin dari `ADMIN_EMAIL` / `ADMIN_PASSWORD` |
| EWS | ambang sensor awal, wilayah **Titik Area 2** (ID alat 1) dan titik **Titik Area 1** |
| Profil Desa | angka wilayah dan batas desa dari kajian pemetaan bencana, 5 potensi desa beserta 20 foto (juga masuk Galeri) |
| Toko | 3 pilihan marketplace, 5 produk awal (tanpa harga dan foto) |
| Edukasi | 3 kategori awal (tanpa materi) |
| Desain Web | semua slot gambar dengan foto bawaan desa |

Data karangan (nama perangkat, berita, nomor telepon palsu, materi dan catatan panen contoh) **tidak** ikut terisi di server.
Untuk latihan di komputer lokal tersedia `ProfilContohSeeder`, `EdukasiContohSeeder`, dan `TokoContohHasilSeeder`; ketiganya menolak jalan di server.

## 4. Yang harus diisi admin setelah masuk
Masuk di `/masuk`, lalu **ganti kata sandi** bila perlu. Isi lewat panel admin:
- **Profil Desa**: nama kepala desa, sambutan, sejarah, visi dan misi, alamat dan kontak kantor, foto kepala desa (menu Desain Web, slot "Foto kepala desa").
- **Perangkat desa** (bisa dengan foto), **Lembaga desa**, serta **Berita & Artikel**.
- **Toko**: harga, foto, dan tautan marketplace tiap produk.
- **Edukasi**: materi belajar.
- **EWS**: cek **ID alat** di Daftar wilayah (harus sama dengan `WILAYAH_ID` di ESP32; awalnya 1), periksa ambang sensor, dan uji kiriman alat.

Sebelum dibuka untuk warga, pastikan tidak ada tulisan "(contoh)" dan nomor `0812-0000-0000` di halaman publik.

## 5. Tugas terjadwal (cron)
Beberapa tugas rutin (membersihkan log EWS, memperpanjang token Shopee, sinkron Shopee dan Google Sheets) berjalan lewat penjadwal Laravel. Pasang satu cron tiap menit:

`* * * * * cd /jalur/ke/proyek && php artisan schedule:run >> /dev/null 2>&1`

Untuk MQTT, ikuti `docs/ews/PANDUAN_MQTT.md` (jalankan `php artisan ews:mqtt` terus-menerus, atau `EWS_MQTT_JADWAL=true` bila hosting hanya punya cron).

## 6. Setelah memperbarui kode
`git pull`, lalu `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `npm run build`, dan `php artisan config:cache route:cache view:cache`.
Bila ada seeder baru, jalankan `php artisan db:seed --force`.
