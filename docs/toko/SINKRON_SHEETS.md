# Sinkron Hasil Panen dengan Google Sheets

Data panen kopi, panen jamur, dan penjualan jamur bisa diedit di Google Sheets, dan perubahannya
masuk ke sistem otomatis (biasanya dalam 10–15 detik). Sebaliknya, perubahan dari panel admin juga
muncul di Sheet. Halaman web yang sedang dibuka ikut berubah sendiri, tanpa perlu dimuat ulang. Untuk memasukkan banyak catatan sekaligus, tempel saja barisnya di tab yang sesuai
dengan kolom ID dikosongkan.

## Cara kerja singkat

- Satu Google Sheet, tiga tab: **Panen kopi**, **Panen jamur**, **Penjualan jamur**.
- Kolom pertama **ID** dan kolom terakhir **Status** diisi sistem (berwarna abu-abu). Jangan diubah.
- Tiap 10 detik sistem membaca Sheet, membandingkannya dengan database, lalu menyamakan keduanya.
  Sheet yang sedang dibuka di browser langsung menampilkan tulisan dari sistem (fitur bawaan Google Sheets).
- Halaman `/toko/hasil` dan daftar admin (Panen kopi, Panen jamur, Penjualan jamur) bertanya ke
  `/api/toko/hasil/versi` tiap 5 detik ("apakah ada data baru?"). Bila ada, angka, tabel, dan grafik diganti
  di tempat tanpa memuat ulang halaman; pilihan grafik dan halaman tabel yang sedang dilihat tetap.
  Tab yang tidak sedang dilihat tidak bertanya. Kodenya: `resources/js/toko/lib/segar-otomatis.js`.
- Di panel admin (daftar Panen kopi, Panen jamur, Penjualan jamur) ada tombol **Buka Google Sheets** yang
  langsung membuka tab yang sesuai. Tombol hanya tampil bila `TOKO_SHEETS_AKTIF=true`. Yang bisa membuka
  Sheet tetap hanya akun Google yang sudah diundang.
- Di halaman publik `/toko/hasil`, tombol **Unduh Excel** diganti **Buka Google Sheets** yang membuka Sheet
  yang sama. Warga yang tidak diundang akan melihat halaman Google "Anda memerlukan akses". Selama
  sinkron Sheets mati, tombol Unduh Excel yang tampil.
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
6. Nyalakan penjadwal supaya sinkron berjalan tiap 10 detik:
   - Lokal: biarkan `php artisan schedule:work` berjalan di satu terminal. Tanpa ini, perubahan di Sheet
     tidak pernah masuk ke sistem.
   - Produksi: pasang cron `* * * * * cd /path/ke/proyek && php artisan schedule:run >> /dev/null 2>&1`.
     Cron cukup tiap menit; Laravel sendiri yang menjalankan sinkron tiap 10 detik di dalam menit itu
     (prosesnya hidup sepanjang menit, hanya bila `TOKO_SHEETS_AKTIF=true`).
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
| Data sudah masuk tapi halaman tidak berubah sendiri | Muat ulang halaman sekali (skrip lama masih tersimpan di browser). Pastikan `npm run dev` jalan atau sudah `npm run build` |
| Tab dilewati: "Judul kolom tidak sesuai" | Baris judul diubah. Jalankan `php artisan toko:sheets-siapkan` |
| Edit yang baru diketik "hilang" | Terjadi bila bersamaan dengan putaran sinkron (jarak beberapa detik). Ketik ulang; baris yang salah dijelaskan di kolom Status |

## Batasan

- Hampir instan, bukan seketika: Sheet → sistem paling lama ~10 detik, lalu halaman web paling lama ~5 detik
  (lebih lama bila sinyal putus-putus; halaman bertanya makin jarang sampai sinyal pulih).
- Penulisan ke Sheet hanya terjadi saat ada yang berubah. Bila bertepatan dengan seseorang yang sedang
  mengetik di baris yang sama, ketikan itu bisa tertimpa dan perlu diulang.
- Hanya satu Sheet yang disinkronkan, dan tiap tab paling banyak 20.000 baris.
