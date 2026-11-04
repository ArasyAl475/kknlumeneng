# CLAUDE.md — Web Terpadu Desa Lumeneng (EWS Longsor · Toko · Edukasi)

Dibaca otomatis oleh Claude Code di awal sesi. Jaga tetap ringkas (< 200 baris).
Rincian panjang ada di berkas yang ditautkan; baca hanya saat dibutuhkan.

## 1. Proyek ini apa
Satu aplikasi Laravel untuk program KKN / Aksara Peduli di Desa Lumeneng
(Kec. Paninggaran, Kab. Pekalongan). Mitra bagian EWS: Karang Taruna Giri
(25 pemuda, kader tanggap bencana). Pengguna akhir: warga desa dan kader,
banyak yang awam teknologi, memakai HP dan sinyal sering lemah.

| Modul | Isi | Pemilik |
|---|---|---|
| Landing | halaman depan + menu ke modul lain | [ISI NAMA] |
| EWS | dashboard peringatan dini longsor (publik) + API untuk alat ESP32 | [ISI NAMA] |
| Admin | panel admin satu pintu; tiap modul menambah menunya sendiri | [ISI NAMA] (kerangka) |
| Toko | e-commerce produk desa (kopi, jamur tiram, dll.) | [ISI NAMA] |
| Edukasi | halaman edukasi (mitigasi longsor, ekonomi sirkular) | [ISI NAMA] |

Alat EWS (ESP32 + sensor) dibuat tim lain. Sensor: kelembapan tanah, curah hujan,
kemiringan, dan udara (sementara diartikan kelembapan udara, belum dikonfirmasi tim).
Ini alat bantu, BUKAN peringatan resmi BPBD. Selalu tampilkan disclaimer di halaman EWS.

Aturan domain EWS: `docs/ews/ATURAN_EWS.md`. Kontrak API untuk alat: `docs/ews/API_ALAT.md`.
Cara memasang proyek: `README.md`.

## 2. Cara bekerja dengan pengguna (pemula)
- Pakai bahasa Indonesia sederhana. Jangan menganggap pengguna paham istilah teknis;
  jelaskan istilah baru dalam satu kalimat.
- Baca bagian proyek yang relevan secara menyeluruh SEBELUM mengusulkan perubahan.
  Jangan menebak isi berkas atau struktur folder.
- Perintah terminal: tulis berurutan (1, 2, 3), satu perintah per langkah, jelaskan
  fungsinya dan hasil yang seharusnya terlihat.
- Setelah tugas selesai: sebutkan berkas yang berubah dan cara mengeceknya di browser.
- Keputusan produk yang tidak ada di dokumen: tanya satu pertanyaan singkat, jangan mengarang.
- Minta konfirmasi sebelum: hapus massal, `git push`, `git reset --hard`,
  `migrate:fresh` / `db:wipe`, atau mengubah berkas bersama (bagian 6).

## 3. Stack
- Laravel stable terbaru (versi mengikuti `composer.json`), PHP sesuai `require.php`.
- Blade + Tailwind CSS lewat Vite. TANPA Livewire/Inertia/React (sengaja sederhana).
- JavaScript biasa (ES module) di `resources/js/`. Leaflet (peta) dan Chart.js (grafik)
  dipasang lewat npm. DILARANG memakai CDN untuk skrip/CSS (tidak tahan sinyal lemah).
- Database: SQLite untuk lokal (berkas tidak di-commit), MySQL untuk produksi.
  Tulis kode yang netral terhadap jenis database.
- Tes: PHPUnit (`php artisan test`). Format: Laravel Pint (`vendor/bin/pint`).
- Zona waktu `Asia/Jakarta`. Bahasa antarmuka: Indonesia.

## 4. Perintah umum
```
composer install                 # pasang dependensi PHP
cp .env.example .env             # Windows PowerShell: copy .env.example .env
php artisan key:generate         # buat kunci aplikasi
php artisan migrate --seed       # buat tabel + data awal
npm install                      # pasang dependensi JS
npm run dev                      # terminal 1: Vite
php artisan serve                # terminal 2: server lokal http://localhost:8000
php artisan test                 # jalankan tes
vendor/bin/pint                  # rapikan kode (CI memakai: vendor/bin/pint --test)
npm run build                    # cek build produksi
```
Jika `composer.json` punya skrip `composer run dev`, itu boleh dipakai sebagai jalan pintas.

## 5. Struktur folder (satu modul = satu set folder sendiri)
```
app/Http/Controllers/{Landing,Ews,Toko,Edukasi}/   controller publik
app/Http/Controllers/Admin/{Ews,Toko,Edukasi}/     controller admin per modul
app/Http/Requests/{Ews,Toko,Edukasi}/              validasi input (FormRequest)
app/Models/{Ews,Toko,Edukasi}/                     model (set $table eksplisit)
app/Services/{Ews,Toko,Edukasi}/                   logika bisnis (bukan di controller/Blade)
routes/web/<modul>.php   routes/admin/<modul>.php  routes/api/<modul>.php
resources/views/{landing,ews,toko,edukasi}/        tampilan publik
resources/views/admin/{ews,toko,edukasi}/          tampilan admin
resources/views/admin/menu/<modul>.blade.php       menu sidebar admin milik modul
resources/views/{layouts,components}/              BERSAMA (lihat bagian 6)
resources/js/<modul>/   resources/css/app.css
database/migrations/ (nama berisi nama modul)   database/seeders/<Modul>Seeder.php
tests/Feature/<Modul>/   tests/Unit/<Modul>/
docs/   firmware/
```
- Tabel diberi awalan modul: `ews_*`, `toko_*`, `edukasi_*`. Tabel bersama: `users`, dst.
- Rute: `routes/web.php` dan `routes/admin.php` hanya memuat semua berkas di
  `routes/web/*.php` dan `routes/admin/*.php` otomatis. Menambah modul = menambah berkas
  rute baru, TANPA mengedit berkas rute bersama. Nama rute: `ews.*`, `admin.ews.*`, `toko.*`.
- Menu publik/landing membaca `config/modul.php` (bendera `MODUL_TOKO`, `MODUL_EDUKASI`, dst.
  di `.env`); modul yang belum siap tidak tampil di menu.

## 6. Berkas bersama (jangan diubah sambil mengerjakan fitur modul)
`resources/views/layouts/*`, `resources/views/components/*`, `resources/css/app.css`,
`routes/web.php`, `routes/admin.php`, `bootstrap/app.php`, `config/modul.php`,
`database/seeders/DatabaseSeeder.php`, `composer.json`, `package.json` (+ berkas lock),
`.github/**`, `CLAUDE.md`.
Perubahan di sini: buat branch `fondasi/<topik>`, PR kecil terpisah, kabari grup.
Perlu komponen UI baru yang dipakai banyak modul? Tambahkan di `components/` lewat PR fondasi.

## 7. Gaya kode
- Nama domain memakai bahasa Indonesia (Wilayah, Pembacaan, Produk); komentar Indonesia
  yang menjelaskan KENAPA, bukan APA.
- Controller tipis: validasi di FormRequest, logika di `Services`, query di Model/Service.
- Tidak ada logika bisnis di Blade atau JavaScript. Aturan status EWS hanya di satu tempat
  (`App\Services\Ews\PenilaianStatus`), jangan diduplikasi.
- Gunakan Eloquent, hindari query di dalam perulangan (N+1). Beri index pada kolom yang difilter.
- Migrasi yang sudah masuk `main` TIDAK boleh diedit; buat migrasi baru.
- Setiap fitur punya tes. Aturan penilaian EWS wajib punya tes unit lengkap.

## 8. Keamanan (wajib)
- `.env`, token, dan kunci tidak boleh di-commit, dicetak, atau ditempel ke chat/log.
  Hanya `.env.example` (tanpa nilai rahasia) yang masuk Git.
- Semua rute admin SELALU lewat middleware `auth` + `admin`. Tidak ada saklar .env untuk
  mematikan login. Simulator hanya untuk admin dan hanya aktif di lingkungan non-produksi.
- Validasi semua input. Tampilkan data dengan `{{ }}`; jangan `{!! !!}` untuk data pengguna.
- API alat: header `X-API-KEY`, bandingkan dengan `hash_equals`, batasi laju (throttle).
- Produksi: `APP_DEBUG=false`, HTTPS, kata sandi admin kuat (seeder menolak kata sandi lemah
  di luar lokal). Jangan mencatat token/kunci ke log.
- Pengiriman Telegram tidak boleh menahan respons API alat (pakai antrean atau
  `dispatchAfterResponse`, timeout pendek).

## 9. Antarmuka (UI)
- Mobile-first. Tombol/target sentuh minimal 44px. Teks mudah dibaca, kontras tinggi.
- Status TIDAK boleh hanya berupa warna: selalu warna + ikon + kata (Aman / Waspada / Awas).
- Hormati `prefers-reduced-motion`. Ada keadaan kosong dan keadaan galat yang ramah.
- Tanggal di UI: `d-m-Y H:i` WIB. Angka desimal di UI memakai koma; di API memakai titik.
- Dashboard EWS harus tetap terbaca di sinyal lemah (halaman ringan, pembaruan data
  berkala 5–10 detik lewat satu endpoint JSON yang di-cache).

## 10. Git
- Jangan commit/push langsung ke `main`. Branch: `<modul>/<topik-singkat>`
  (`ews/grafik-hujan`, `toko/keranjang`, `edukasi/modul-longsor`, `fondasi/menu-admin`).
- Pesan commit: `<modul>: <apa yang berubah>` (Indonesia, singkat, kalimat perintah).
- Sebelum PR: jalankan `/cek`. PR kecil, satu topik, ikuti template PR.
- Hanya ubah berkas di modul sendiri. Menyentuh modul orang lain = bicarakan dulu.

## 11. Kontrak API alat
Firmware alat sudah mengikuti `/api/sensor`, `/api/ping`, dan nama field di `docs/ews/API_ALAT.md`.
Jangan mengubahnya tanpa persetujuan pembuat alat.
