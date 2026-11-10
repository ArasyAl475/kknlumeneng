# Sinkron Hasil Panen dengan Google Sheets

Data panen kopi, panen jamur, dan penjualan jamur bisa diedit di Google Sheets, dan perubahannya
masuk ke sistem otomatis (maksimal sekitar 1 menit). Sebaliknya, perubahan dari panel admin juga
muncul di Sheet. Untuk sekali unggah banyak data dari berkas Excel, pakai tombol **Impor dari Excel**
di panel admin (fitur terpisah, tidak butuh Google).

## Cara kerja singkat

- Satu Google Sheet, tiga tab: **Panen kopi**, **Panen jamur**, **Penjualan jamur**.
- Kolom pertama **ID** dan kolom terakhir **Status** diisi sistem (berwarna abu-abu). Jangan diubah.
- Tiap menit sistem membaca Sheet, membandingkannya dengan database, lalu menyamakan keduanya.
- Sistem yang menarik data dari Google. Tidak ada alamat web yang dibuka untuk Google, dan kunci
  rahasia hanya ada di server.

## Aturan untuk yang mengedit Sheet

| Yang dilakukan di Sheet | Hasilnya |
|---|---|
| Ubah isian di baris yang sudah ada | Catatan di sistem ikut berubah |
| Tambah baris baru (kolom ID dikosongkan) | Menjadi catatan baru, ID terisi otomatis |
| Isian salah (tanggal ngawur, angka bukan angka) | Tidak ada yang berubah. Kolom Status berisi "Perlu diperbaiki: ..." |
| Hapus baris | Catatan **tidak** dihapus. Baris dikembalikan dengan status "Dikembalikan" |
| Ubah sel yang sama dengan yang diubah di panel admin | Data di sistem yang dipakai. Status berisi "Konflik" |

- Menghapus catatan hanya lewat panel admin. Barisnya otomatis hilang dari Sheet.
- Tanggal ditulis `05-10-2026` (hari-bulan-tahun). Angka desimal boleh memakai koma (`100,5`).
- Aturan isian sama dengan form admin (tanggal tidak boleh melewati hari ini, maksimal 2 angka desimal, dst.).
- Jangan mengubah baris judul (baris 1) atau urutan kolom. Kalau diubah, tab itu dilewati sampai
  `php artisan toko:sheets-siapkan` dijalankan lagi.

## Pemasangan (sekali saja)

### A. Siapkan Google (dikerjakan pemilik akun Google)

1. Buka <https://console.cloud.google.com/>, buat **proyek baru** (nama bebas, mis. "Web Lumeneng").
2. Menu **APIs & Services → Library**, cari **Google Sheets API**, klik **Enable**.
3. Menu **IAM & Admin → Service Accounts → Create service account**. Isi nama bebas, lewati pilihan peran, klik Done.
4. Klik akun layanan yang baru dibuat → tab **Keys → Add key → Create new key → JSON**. Sebuah berkas `.json` terunduh.
   Berkas ini = kata sandi. Jangan dikirim lewat chat/email dan jangan di-commit ke Git.
5. Di halaman akun layanan, salin **alamat email**-nya (berbentuk `nama@proyek.iam.gserviceaccount.com`).
6. Buat **Google Sheet kosong** baru. Klik **Bagikan**, tempel email akun layanan, beri peran **Editor**.
   Bagikan juga ke admin yang berhak mengedit. **Jangan** pilih "Siapa saja yang memiliki link".

### B. Siapkan server/komputer

1. Simpan berkas `.json` di luar folder proyek, mis. `C:\laragon\rahasia\sheets-lumeneng.json`.
2. Salin ID Sheet dari alamat: `https://docs.google.com/spreadsheets/d/`**`ID_DI_SINI`**`/edit`.
3. Isi `.env`:
   ```
   TOKO_SHEETS_AKTIF=true
   TOKO_SHEETS_ID=ID_DI_SINI
   TOKO_SHEETS_KUNCI=C:\laragon\rahasia\sheets-lumeneng.json
   ```
4. `php artisan migrate` (membuat tabel pencatat status sinkron).
5. `php artisan toko:sheets-siapkan` → membuat tab, judul kolom, format, lalu mengisi Sheet dengan
   catatan yang sudah ada. Hasil yang benar: muncul hitungan per tab, dan Sheet berisi data.
6. Nyalakan penjadwal supaya sinkron berjalan tiap menit:
   - Lokal: biarkan `php artisan schedule:work` berjalan di satu terminal.
   - Produksi: pasang cron `* * * * * cd /path/ke/proyek && php artisan schedule:run >> /dev/null 2>&1`.
     Kalau hosting tidak menyediakan cron, sinkron otomatis tidak berjalan; `php artisan toko:sheets-sinkron`
     tetap bisa dijalankan manual.

## Keamanan

- Sheet ini berisi **biaya baglog dan uang penjualan** (data khusus admin). Bagikan hanya ke admin.
  Siapa pun yang bisa mengedit Sheet bisa mengubah angka di halaman Hasil Panen publik.
- Berkas kunci dan `.env` tidak boleh masuk Git. Bila kunci bocor: di Google Cloud, hapus kunci itu,
  buat kunci baru, lalu ganti berkasnya.
- Setiap perubahan dari Sheet tercatat di **Log aktivitas** Toko ("Sinkron Google Sheets").
- Mencabut akses kapan saja: hapus email akun layanan dari daftar bagikan Sheet, atau set `TOKO_SHEETS_AKTIF=false`.

## Bila ada masalah

| Gejala | Penyebab / jalan keluar |
|---|---|
| `toko:sheets-siapkan` gagal 403/404 | Sheet belum dibagikan ke email akun layanan, atau ID Sheet salah |
| Gagal 403 "API has not been used" | Google Sheets API belum di-Enable di proyek yang sama dengan akun layanan |
| Perubahan di Sheet tidak masuk | Penjadwal tidak berjalan; cek `php artisan toko:sheets-sinkron` dan `storage/logs/laravel.log` |
| Tab dilewati: "Judul kolom tidak sesuai" | Baris judul diubah. Jalankan `php artisan toko:sheets-siapkan` |
| Edit yang baru diketik "hilang" | Terjadi bila bersamaan dengan putaran sinkron (jarak beberapa detik). Ketik ulang; baris yang salah dijelaskan di kolom Status |

## Batasan

- Tidak instan: jeda maksimal sekitar 1 menit.
- Penulisan ke Sheet hanya terjadi saat ada yang berubah. Bila bertepatan dengan seseorang yang sedang
  mengetik di baris yang sama, ketikan itu bisa tertimpa dan perlu diulang.
- Hanya satu Sheet yang disinkronkan, dan tiap tab paling banyak 20.000 baris.
